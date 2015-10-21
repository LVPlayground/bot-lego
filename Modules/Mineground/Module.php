<?php
/**
 * Copyright (c) 2006-2013 Las Venturas Mineground
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

use Nuwani \ BotManager;
use Nuwani \ ModuleManager;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/CommandHelper.php';
require_once __DIR__ . '/Commands.php';
require_once __DIR__ . '/PermissionsHandler.php';
require_once __DIR__ . '/ServerController.php';
require_once __DIR__ . '/AntiCheatHandler.php';
require_once __DIR__ . '/TombstoneHandler.php';
require_once __DIR__ . '/EconHandler.php';

use Mineground \ AntiCheatHandler;
use Mineground \ Commands;
use Mineground \ Configuration;
use Mineground \ EconHandler;
use Mineground \ TombstoneHandler;

class Mineground extends ModuleBase {
  const CommandPrefix = '!';

  private $m_AntiCheat;

  public function __construct() {
    TombstoneHandler::updateWorldList();
    $this->m_AntiCheat = new AntiCheatHandler();
  }

  // Invoked when someone types something in a public channel.
  public function onChannelPrivmsg(Bot $bot, $channel, $nickname, $message) {
    if (substr($message, 0, 1) != self::CommandPrefix)
      return;

    $channelTracker = ModuleManager::getInstance()->offsetGet('ChannelTracker');
    if ($channelTracker === false) {
      echo '[Mineground] Disregarding command as the Channel Tracker is not available.' . PHP_EOL;
      return;
    }

    $userLevel = $channelTracker->highestUserLevelForChannel($nickname, Configuration::value('CrewChannel'));
    $parameters = preg_split('/\s+/', $message);
    $command = substr(array_shift($parameters), 1);

    return Commands::processCommand($bot, $command, $parameters, $channel, $nickname, $userLevel);
  }

  // Invoked when someone types something in private chat to the bot.
  public function onPrivmsg(Bot $bot, $nickname, $message) {
    if (substr($message, 0, 1) != self::CommandPrefix)
      return;

    $parameters = preg_split('/\s+/', $message);
    $command = substr(array_shift($parameters), 1);

    return Commands::processPrivateCommand($bot, $command, $parameters, $nickname);
  }
};