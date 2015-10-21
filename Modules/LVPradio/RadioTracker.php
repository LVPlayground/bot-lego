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

namespace LVPradio;

use \ Nuwani;
use \ LVPradio;

// Tracks the messages send by the bot in order to update the current connected DJ
class RadioTracker {
  private static $m_currentDJ;

  public static function ParseMessage($message) {
    if (preg_match('/\[LVP Radio\] .* is off --> Coming up: (.*)?]/', $message, $matches))
      self::$m_currentDJ = $matches[1];

    else if (preg_match('/\[LVP Radio\] Current DJ: (.*)?]/', $message, $matches))
      self::$m_currentDJ = $matches[1];
  }

  public static function getAutoDJState() {
    if (self::$m_currentDJ == 'LVP_Radio')
      return true;
    else
      return false;
  }

  public static function setCurrentDJ($nickname) {
    self::$m_currentDJ = $nickname;
  }
};