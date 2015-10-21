<?php
/**
 * Copyright (c) 2006-2013 Las Venturas Playground
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 */

use Nuwani \ ModuleManager;

class UserStatus {
    const IsVisitor = 1;
    const IsVoiced = 2;
    const IsHalfOperator = 4;
    const IsOperator = 8;
    const IsProtected = 16;
    const IsOwner = 32;
};

class ChannelTracker extends ModuleBase {
    public $m_channels;
    
    public function userLevelForChannel($nickname, $channel) {
        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            return 0;
        
        if (isset($this->m_channels[$channel][$nickname]) === false)
            return 0;
        
        return $this->m_channels[$channel][$nickname];
    }
    
    public function highestUserLevelForChannel($nickname, $channel) {
        $status = $this->userLevelForChannel($nickname, $channel);
        if ($status & UserStatus::IsOwner)
            return UserStatus::IsOwner;
        if ($status & UserStatus::IsProtected)
            return UserStatus::IsProtected;
        if ($status & UserStatus::IsOperator)
            return UserStatus::IsOperator;
        if ($status & UserStatus::IsHalfOperator)
            return UserStatus::IsHalfOperator;
        if ($status & UserStatus::IsVoiced)
            return UserStatus::IsVoiced;
    
        return UserStatus::IsVisitor;
    }
    
    public function onChannelJoin(Bot $bot, $channel, $nickname) {
        if ($nickname == $bot['Nickname'])
            $bot->send('NAMES ' . $channel);
        
        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            $this->m_channels[$channel] = array();
        
        $this->m_channels[$channel][$nickname] = UserStatus::IsVisitor;
    }
    
    public function onChannelLeave(Bot $bot, $channel, $nickname) {
        $channel = strtolower($channel);
        if ($nickname == $bot['Nickname']) {
            unset($this->m_channels[$channel]);
            return;
        }
        
        if (isset($this->m_channels[$channel]) === false || isset($this->m_channels[$channel][$nickname]) === false)
            return;
        
        unset($this->m_channel[$channel][$nickname]);
    }
    
    public function onChannelKick(Bot $bot, $channel, $kicked, $kicker, $reason) {
        $this->onChannelLeave($bot, $channel, $kicked);
    }
    
    public function onChannelPart(Bot $bot, $channel, $nickname, $reason) {
        $this->onChannelLeave($bot, $channel, $nickname);
    }
    
    public function onQuit(Bot $bot, $nickname, $reason) {
        if ($nickname == $bot['Nickname'])
            return;
        
        foreach ($this->m_channels as $channel => &$users) {
            if (isset($users[$nickname]))
                unset($users[$nickname]);
        }
    }
    
    public function onChangeNick(Bot $bot, $formerNickname, $nickname) {
        foreach ($this->m_channels as $channel => &$users) {
            if (isset($users[$formerNickname]) === false)
                continue;
        
            $users[$nickname] = $users[$formerNickname];
            unset($users[$formerNickname]);
        }
    }
    
    public function onChannelNames(Bot $bot, $channel, $names) {
        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            return;
    
        foreach (preg_split('/\s+/', $names, -1, PREG_SPLIT_NO_EMPTY) as $user) {
            $level = UserStatus::IsVisitor;
            $offset = 0;

            for ($length = strlen($user); $offset < $length; ++$offset) {
                switch (substr($user, $offset, 1)) {
                    case '~':
                        $level |= UserStatus::IsOwner;
                        break;
                    case '&':
                        $level |= UserStatus::IsProtected;
                        break;
                    case '@':
                        $level |= UserStatus::IsOperator;
                        break;
                    case '%':
                        $level |= UserStatus::IsHalfOperator;
                        break;
                    case '+':
                        $level |= UserStatus::IsVoiced;
                        break;
                    default:
                        break 2;
                }
            }
            
            $this->m_channels[$channel][substr($user, $offset)] = $level;
        }
    }
    /*
    public function onChannelPrivmsg(Bot $bot, $channel, $nickname, $message) {
        if (substr($message, 0, 5) != '!test')
            return;
        
        $channel = strtolower($channel);
        $message = '';
        
        foreach ($this->m_channels[$channel] as $nickname => $level) {
            if ($level & UserStatus::IsOwner)
                $message .= '~';
            if ($level & UserStatus::IsProtected)
                $message .= '&';
            if ($level & UserStatus::IsOperator)
                $message .= '@';
            if ($level & UserStatus::IsHalfOperator)
                $message .= '%';
            if ($level & UserStatus::IsVoiced)
                $message .= '+';
            
            $message .= preg_replace('/(.)(.)(.*)/', '\1_\3', $nickname);
            $message .= ' ';
        }
        
        $bot->send('PRIVMSG ' . $channel . ' :' . $message);
    
    }
    */
    public function onChannelMode(Bot $bot, $channel, $modes) {
        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            return;
        
        $modes = preg_split('/\s+/', $modes, -1, PREG_SPLIT_NO_EMPTY);
        $commandOperator = 1;
        $modeAddition = true;
        
        for ($index = 0, $length = strlen($modes[0]); $index < $length; ++$index) {
            switch ($modes[0][$index]) {
                case '+':
                    $modeAddition = true;
                    break;
                case '-':
                    $modeAddition = false;
                    break;
                
                case 'q':
                case 'a':
                case 'o':
                case 'h':
                case 'v':
                    $right = $this->rightForChannelMode($modes[0][$index]);
                    if ($modeAddition === true)
                        $this->m_channels[$channel][$modes[$commandOperator++]] |= $right;
                    else
                        $this->m_channels[$channel][$modes[$commandOperator++]] &= ~ $right;
                    break;
                
                case 'b':
                case 'k':
                case 'l':
                case 'd':
                case 'e':
                case 'F':
                case 'f':
                case 'g':
                case 'H':
                case 'l':
                case 'J':
                case 'j':
                case 'L':
                case 'w':
                case 'W':
                    if ($modeAddition === true)
                        $commandOperator++;
                    break;
            }
        }
    }
    
    private function rightForChannelMode($mode) {
        switch ($mode) {
            case 'q':
                return UserStatus::IsOwner;
            case 'a':
                return UserStatus::IsProtected;
            case 'o':
                return UserStatus::IsOperator;
            case 'h':
                return UserStatus::IsHalfOperator;
            case 'v':
                return UserStatus::IsVoiced;
        }
        
        return UserStatus::IsVisitor;
    }
};