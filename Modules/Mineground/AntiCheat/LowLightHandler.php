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
namespace Mineground\AntiCheatHandler;

use Nuwani;
use Nuwani \ BotManager;

use Mineground \ Configuration;

/**
 * Handles monitoring of the LowLight logfile
 *
 * @author Joeri de Graaf <joeri@oostcoast.nl>
 */
class LowLightHandler {
  const NoticeColour  = "\00306";
  const WarningColour = "\00304";

  private static $m_lowLightNotices = array();
  private static $m_lowLightFile;
  private static $m_processLowLightNotices = true;

  public static function Initialize() {
    self::$m_lowLightFile = Configuration::value('LowLightFile');
    self::$m_lowLightNotices = self::getLowLightNotices();

    if (!self::$m_lowLightNotices){
      self::$m_processLowLightNotices = false;
    } 
  }

  /**
   * Checks whether a change has occured in the lowlight lowfile and echoes those changes
   *
   * @param  Bot    $bot     Bot array
   * @param  string $channel Channel to send the output to
   */
  public static function processLowLightNotices() {
    if (!self::$m_processLowLightNotices)
      return var_dump('Cannot load the LowLight File');

    $newLowLightNotices = array_diff(self::getLowLightNotices(), self::$m_lowLightNotices);

    self::$m_lowLightNotices = self::getLowLightNotices();

    $channel = Configuration::value('CrewChannel');

    foreach ($newLowLightNotices as $lowLightNotice) {
      $bot = BotManager::getInstance()->offsetGet('channel:' . $channel);
      if ($bot === false)
        continue;
         
      if ($bot instanceof \ Nuwani \ BotGroup)
        $bot = $bot->current();
      $bot->send('PRIVMSG ' . $channel . ' :' . $lowLightNotice);
    }
  }

  private static function getLowLightNotices() {
    if(!file_exists(self::$m_lowLightFile))
      return false;

    $lowLightFileContents = file_get_contents(self::$m_lowLightFile);
    $lowLightFileContents = explode(PHP_EOL, $lowLightFileContents);
  
    if ($lowLightFileContents[0] == "")
      return array(self::NoticeColour . 'Lowlight: The logfile has been emptied');

    foreach ($lowLightFileContents as $key => $line) {
      if (preg_match('/^.* broken by .*$/', $line))
        $lowLightFileContents[$key] = self::NoticeColour . 'LowLight: ' .  
          preg_replace('/^\[.*\] (.*) broken by (.*) at \(x: (-?\d+), y: (-?\d+), z: (-?\d+)\) in (\w+)$/', '$2 broke $1 @ $6: $3, $4, $5', $line);
      else if (preg_match('/^.* mined .*$/', $line))
        $lowLightFileContents[$key] = self::WarningColour .  'LowLight: ' . 
          preg_replace('/^\[.*]\ (.*) mined (.*) below (\d+%) light$/', '$1 mined below $3 light!', $line);
      else
        $lowLightFileContents[$key] = self::NoticeColour . 'LowLight: Error - unknown notice format for "' . $line . '"';
    }
    return $lowLightFileContents;
  }


};
?>