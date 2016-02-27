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
    $serialNotices = self::getSerialNotices();

    if (!is_array($serialNotices))
      return false;

    foreach ($serialNotices as $serialNotice) {
      if ($serialNotice == '')
        continue;

      $serialNotice = explode(',', $serialNotice);

      $banReason = BanManager::isSerialBanned($serialNotice[3]);

      if (!$banReason)
        continue;
      
      if (!PlayerTracker::isPlayerConnected($serialNotice[1])) {
        echo "Error: player $serialnotice[0] is no longer connected";
        //continue;
      }
      
      $bot = BotManager::getInstance()->offsetGet('channel:' . '#LVP.echo');
      if ($bot === false)
        continue;
         
      if ($bot instanceof \ Nuwani \ BotGroup)
        $bot = $bot->current();

      $bot->send('PRIVMSG ' . '#LVP.echo' . ' :' . '!ban ' . $serialNotice[1] . ' Ban Evading (Code 3)');
      CommandHelper::infomessage($bot, '#LVP.Crew', 'Banned ' . $serialNotice[0] . ' reason: ' . $banReason);
    }
  }

  private static function getSerialNotices() {
    if(!file_exists(self::$m_serialFile))
      return false;

    $serialFileContents = file_get_contents(self::$m_serialFile);
  
    file_put_contents(self::$m_serialLogFile, $serialFileContents, FILE_APPEND);
    file_put_contents(self::$m_serialFile, "");

    $serialFileContents = explode("\r\n", $serialFileContents);

    return $serialFileContents;
  }
};
?>