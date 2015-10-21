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
 * Handles the economy related commands through parsing the BOSE economy accounts file
 */
class EconHandler {

  private static $m_accounts;

  /**
   * Loads the accounts data into memory
   *
   * @return array List of all accounts
   */
  public static function LoadAccounts() {
    $econFile = Configuration::value('EconomyFile');
    if (!file_exists($econFile))
      return false;

    $accountData = file_get_contents($econFile);

    preg_match_all('/(\S+)\s*\{\s*type\s+\S+\s*money\s+([\d.]+)\s*\}/', $accountData, $ParsedAccountData);
    $accountData = array_combine($ParsedAccountData[1], $ParsedAccountData[2]);
    arsort($accountData);

    if (empty($accountData))
      return false;

    self::$m_accounts = $accountData;
    return true;
  }

  /**
   * Returns an array containing the most wealthiest players 
   *
   * @param  integer $range How many players you want to be listed
   *
   * @return array         Names and numbers of the players
   */
  public static function listWealthiestPlayers($range = 10) {
    return array_slice(self::$m_accounts, 0, $range);
  }

  /**
   * Returns the amount of econ a player has
   *
   * @param  string $playerName Name of the player to look up
   *
   * @return interger             The amount of econ
   */
  public static function getPlayerEconAmount($playerName) {
    if (array_key_exists($playerName, self::$m_accounts))
      return round(self::$m_accounts[$playerName]);

    return false;
  }
};
?>