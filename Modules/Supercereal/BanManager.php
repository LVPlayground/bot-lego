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

/**
 * Checks with players should be banned and maintains the list
 *
 * @author Joeri de Graaf <joeri@oostcoast.nl>
 */
class BanManager {

  const BanListFile = 'Modules/Supercereal/banlist.json';
  private static $m_banList;

  /**
   * Loads the banlist from disk
   *
   * Format:
   * gpci,nickname
   *
   * @return [type] [description]
   */
  public static function loadBanlist() {
    if (!file_exists(self::BanListFile))
      return false;

    self::$m_banList = json_decode(file_get_contents(self::BanListFile), $assoc = true);

    return true;
  }

  /**
   * Saves the banlist to disk
   *
   * Format: gpci,nickname
   *
   * @return boolean  true if saving was succesfull or false on failure
   */
  public static function saveBanlist() {
    if (!file_exists(self::BanListFile))
      return false;

    return file_put_contents(self::BanListFile, json_encode(self::$m_banList, JSON_PRETTY_PRINT));
  }

  /**
   * Adds an entry to the banlist and saves it
   *
   * Format: gpci, target, issuer
   *
   * @return boolean  true if saving was succesfull or false on failure
   */
  public static function addSerialToBanlist($serial, $target, $issuer) {
    // Add entry
    self::$m_banList[] = array($serial, $target, $issuer, time());

    // Save the banlist to disk
    return self::saveBanlist();
  }

  /**
   * Removes an entry from the banlist and saves it
   *
   * Format: gpci
   *
   */
  public static function removeSerialFromBanList($serial) {
    foreach (self::$m_banList as $key => $entry) {
      if ($entry[0] == $serial)
        unset(self::$m_banList[$key]);
    }

    return self::saveBanlist();
  }


  /**
   * Tests whether a serial is banned
   *
   * @param  string $serial gpci to check
   *
   * @return mixed  false if no match was found or the reason of it was.
   */
  public static function isSerialBanned($serial) {
    if (!is_array(self::$m_banList))
      return false;

    foreach (self::$m_banList as $entry) {
      if ($entry[0] == $serial)
        return $entry[1];
    }

  // return false is nothing is found
  return false;
  }

};
?>
