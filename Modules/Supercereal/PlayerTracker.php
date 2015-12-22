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
 * Maintains a list of connected players
 *
 * @author Joeri de Graaf <joeri@oostcoast.nl>
 */
class PlayerTracker {

    private static $m_connectedPlayerList = array();

    /**
     * Adds a player to the tracker
     */
    public static function addPlayer($playerId, $playerName) {
      self::$m_connectedPlayerList[$playerId] = $playerName;
    }

    /**
     * Removes a player from the tracker
     */
    public static function removePlayer($playerId) {
      unset(self::$m_connectedPlayerList[$playerId]);
    }

    /**
     * Flushes the player list
     */
    public static function flushPlayerTracker() {
      $m_connectedPlayerList = array();
      
      echo "PlayerTracker flushed due to a server restart";
    }

    /**
     * Checks if the $nickname is connected and return their ID or FALSE
     */
    public static function isPlayerConnected($playerName) {
      return in_array($playerName, self::$m_connectedPlayerList);
    }
};
?>
