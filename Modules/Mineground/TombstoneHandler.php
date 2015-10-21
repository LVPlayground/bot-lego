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

namespace Mineground;

use Mineground \ Configuration;

/**
 * Handles parsing the tombstone files
 */
class TombstoneHandler {

  // Holds the list of availible worlds
  private static $m_tombstoneWorldList = array();
  private static $m_tombstoneList = array();
  
  /**
   * Is called on startup, updates the list of world for which tombstones is activated
   *
   * @return boolean If the list was successfully updated
   */
  public static function updateWorldList() {
    if(is_dir(Configuration::value('TombstonesDirectory'))) {
      $worldList = scandir(Configuration::value('TombstonesDirectory'));
      $worldList = array_diff($worldList, array('.', '..', 'config.yml', 'sign.tpl'));
      sort($worldList);
      foreach ($worldList as $key => $world) {
        $worldList[preg_replace('/tombList\-(.*)\.db/', '$1', $worldList[$key])] = $world;
        unset($worldList[$key]);
      }
      self::$m_tombstoneWorldList = $worldList;
      return true;

    }
    return false;
  }

  /**
   * Returns the list of all tombstones in the world specified
   *
   * Array format:
   * - WorldName
   *   -playerName
   *     - 0
   *        -x
   *        -y
   *        -z
   *        -UNIX datetime of destoy moment
   *     - 1
   *     ...
   *
   * @param  string $worldName Name of the world requested
   *
   * @return array            The list of tombstones or FALSE if none were found
   */
  public static function listTombstonesInWorld($worldName) {
    $tombstoneFile = Configuration::value('TombstonesDirectory') . self::$m_tombstoneWorldList[$worldName];
    if (file_exists($tombstoneFile)){
      $tombstones = explode("\n", file_get_contents($tombstoneFile));
      foreach ($tombstones as $key => $tombstoneline) {
        if (!$tombstoneline)
          continue;

        preg_match('/^.*::(\w+),(-?\d+),(-?\d+),(-?\d+):(\w+):(\d+)/', $tombstoneline, $tombstone);
        $tombstoneList[$tombstone[1]][$tombstone[5]][] = array($tombstone[2],$tombstone[3],$tombstone[4],$tombstone[6]);
      }
      return $tombstoneList;
    }      
    return false;
  }

  /**
   * Gives the list of all tombstones owned by that player
   *
   * @param  string $playerName Name of the player to look up
   *
   * @return array             The list or tombstones
   */
  public static function listTombstonesForPlayer($playerName) {
    foreach (self::$m_tombstoneWorldList as $tomstoneworld => $tombstoneFile) {
      $tombstoneFile = Configuration::value('TombstonesDirectory') . $tombstoneFile;
      if (file_exists($tombstoneFile)){
        $tombstones = explode("\n", file_get_contents($tombstoneFile));
        foreach ($tombstones as $key => $tombstoneline) {
          if (!$tombstoneline)
            continue;

          preg_match('/^.*::(\w+),(-?\d+),(-?\d+),(-?\d+):(\w+):(\d+)/', $tombstoneline, $tombstone);
          
          if ($tombstone[5] != $playerName)
            continue;

          $tombstonePlayerList[$tombstone[1]][] = array($tombstone[2],$tombstone[3],$tombstone[4],$tombstone[6]);
        }
      }
    }
    if(empty($tombstonePlayerList))
      return NULL;

    return $tombstonePlayerList;       
  }
  /**
   * Returns the array containing all worlds with tombstones actived
   *
   * @return array The list of worlds
   */
  public static function listTombstonesWorlds() {
    return array_keys(self::$m_tombstoneWorldList);
  }

  /**
   * Tests whether a world is activated for tombstones
   *
   * @param  string  $WorldName Name of the world
   *
   * @return boolean            If it is activated
   */
  public static function isTombstonesWorld($WorldName) {
    return array_key_exists($WorldName, self::$m_tombstoneWorldList);
  }
};
?>