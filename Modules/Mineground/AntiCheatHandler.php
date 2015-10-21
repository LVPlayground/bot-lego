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

use Nuwani \ BotManager;
use Nuwani \ Timer;

require_once __DIR__ . '/AntiCheat/LowLightHandler.php';

use Mineground \ AntiCheatHandler \ LowLightHandler;

/**
 * Handles all monitoring and sending notices regarding anticheat
 *
 * @author Joeri de Graaf <joeri@oostcoast.nl>
 */
class AntiCheatHandler {
  private $m_antiCheatTimer;

  public function __construct() {
    LowLightHandler::Initialize();
    $this->m_antiCheatTimer = new Timer;
    $this->m_antiCheatTimer->create(array($this, 'processAntiCheat'), 5000, 1);
  }

  public static function processAntiCheat() {
    LowLightHandler::processLowLightNotices();
    return true;
  }
};
?>
