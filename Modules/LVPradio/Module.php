<?php
/**
 * Copyright (c) 2006-2013 Las Venturas LVPradio
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

use Nuwani \ Bot;
use Nuwani \ BotManager;
use Nuwani \ ModuleManager;

require_once __DIR__ . '/CommandHelper.php';
require_once __DIR__ . '/Commands.php';
require_once __DIR__ . '/RadioTracker.php';

use LVPradio \ Commands;
use LVPradio \ CommandHelper;
use LVPradio \ RadioTracker;

class LVPradio extends ModuleBase {
  const CommandPrefix = '!';

  // Invoked when someone types something in a public channel.
  public function onChannelPrivmsg(Bot $bot, $channel, $nickname, $message) {
    if (substr($message, 0, 1) != self::CommandPrefix) {
      if ($nickname == 'LVP_Radio' && $channel == '#LVP.Radio')
        RadioTracker::ParseMessage($message);
      return;
    }

    $channelTracker = ModuleManager::getInstance()->offsetGet('ChannelTracker');
    if ($channelTracker === false) {
      echo '[LVPradio] Disregarding command as the Channel Tracker is not available.' . PHP_EOL;
      return;
    }

    $userLevel = $channelTracker->highestUserLevelForChannel($nickname, '#LVP.Radio');
    $parameters = preg_split('/\s+/', $message);
    $command = substr(array_shift($parameters), 1);

    return Commands::processCommand($bot, $command, $parameters, $channel, $nickname, $userLevel);
  }

  // Invoked when someone types something in private chat to the bot.
  public function onPrivmsg(Bot $bot, $nickname, $message) {
    if ($nickname == 'LVP_Radio' && $message == 'Stopping immediately...') {
      CommandHelper::infoMessage($bot, '#LVP.Radio', 'The autodj is stopping and will reconnect within 60 seconds. Start DJ\'ing now!');
    }

    return false;
  }
};