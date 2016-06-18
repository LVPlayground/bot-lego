<?php
/**
 * Copyright (c) 2006-2015 Las Venturas Playground
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
namespace Supercereal;

use Nuwani;
use Nuwani \ Bot;
use Nuwani \ BotManager;
use \ Supercereal;

/**
 * Handles monitoring the gpci file
 *
 * @author Joeri de Graaf <joeri@oostcoast.nl>
 */
class SerialMonitor {

  private static $m_serialFile =    '/home/samp/playground/server/scriptfiles/gpci/gpci.txt';
  public static $m_serialLogFile =  '/home/samp/playground/server/scriptfiles/gpci/gpciLog.txt';

  public function __construct() {
    $this->m_antiCheatTimer = new Nuwani\Timer;
    $this->m_antiCheatTimer->create(array($this, 'processSerialNotices'), 2000, 1);
  }

  /**
   * Loads new gpci notices form the log and check whether they should be banned.
   *
   * @param  Bot    $bot     Bot array
   * @param  string $channel Channel to send the output to
   */
  public static function processSerialNotices() {
    
    // Check if the serial file exists
    if(!file_exists(self::$m_serialFile)) {
      echo "Error: The configured serialFile does not exist" . PHP_EOL;
      exit;
    }

    // Load the contents of the serialFile
    $serialFileContents = file_get_contents(self::$m_serialFile);
    
    // Check whether the serialFile is empty and nothing needs to be processed
    if( $serialFileContents == '')
      return;
    
    // Append the contents of the serialFile to the serialLogFile
    file_put_contents(self::$m_serialLogFile, $serialFileContents, FILE_APPEND);
    
    // Empty the serialFile
    file_put_contents(self::$m_serialFile, "");
    
    // Separate the induvidual lines into notices
    $serialNotices = explode("\r\n", $serialFileContents);

    // Go the through the notices to check for bans
    foreach ($serialNotices as $serialNotice) {
      // Skip empty lines
      if ($serialNotice == "")
        continue;
      
      // Split the notice into the 4 parts
      // nickname, id, ip, serial
      $serialNotice = explode(',', $serialNotice);

      // Get the ban data for the found serial
      $serialBanData = BanManager::isSerialBanned($serialNotice[3]);
      
      // Check whether the serial is allowed in
      if ($serialBanData == false)
        continue;

      // Check whether the player is still connected and is not another player with the same id
      if (!PlayerTracker::isPlayerConnected($serialNotice[1])) {
        echo "Error: player $serialNotice[0] is no longer connected" . PHP_EOL;
        continue;
      }
      
      $bot = BotManager::getInstance()->offsetGet('channel:' . Supercereal::EchoChannel);
      if ($bot === false)
        continue;
         
      if ($bot instanceof \ Nuwani \ BotGroup)
        $bot = $bot->current();

      // Tell Nuwani to ban the id of the evading person
      CommandHelper::channelMessage($bot, Supercereal::EchoChannel, '!ban ' . $serialNotice[1] . ' Ban Evading (Code 3)');
      
      // Notify the crew that someone tried to evade and was denied entry
      CommandHelper::infoMessage($bot, Supercereal::CrewChannel, "'$serialNotice[0]' was denied entry being serial banned for '$serialBanData[0]' on " . date('j/n/Y G:i:s', $serialBanData[2]));
    }
  }
};
?>