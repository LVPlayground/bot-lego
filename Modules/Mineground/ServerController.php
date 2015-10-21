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

use Mineground \ CommandHelper;
use Mineground \ Configuration;

/**
 * Handles starting and stopping the Minecraft server and can send commands to it
 *
 * @author Joeri de Graaf <joeri@oostcoast.nl>
 */
class ServerController {
  public static $m_forbiddenCommands = array('stop',);

  /**
   * Starts the minecraftserver through the init script if it isn't running yet
   *
   * @param  Bot    $bot     Bot array of the bot recieving the command
   * @param  string $channel Channel the command was recieved from
   *
   * @return boolean          If the start was successfull
   */
  public static function startMinecraftServer(Bot $bot, $channel) {
    shell_exec('/etc/init.d/minecraft start');
    CommandHelper::infomessage($bot, $channel, 'Command executed');
    
    return true;
  }

  /**
   * Stops of terminates the minecraft server
   *
   * @param  Bot     $bot       Bot array of the bot recieving the command
   * @param  string  $channel   Channel the command was recieved from
   * @param  boolean $terminate If the server must be stopped of terminated if it hanged
   *
   * @return boolean            If the action was successfull
   */
  public static function stopMinecraftServer(Bot $bot, $channel, $terminate = false) {
    shell_exec('/etc/init.d/minecraft stop');
    CommandHelper::infomessage($bot, $channel, 'Command executed');

    return true;
  }

  /**
   * Sends a command to the server
   * 
   * @param  string $command The command which should be send
   */
  public static function sendCommandToServer($command) {
    //TODO:: Add screen to configuragion and test whether it exists
    if (preg_match('/^[a-zA-Z0-9 ]*$/', $command) && self::isAllowedCommand($command))
      shell_exec('screen -S minecraft -p 0 -X stuff "`printf "' . $command . '\r"`";');
    return true;
  }

  /**
   * Checks if a command is not blacklisted
   *
   * @param  string  $command The command to test
   *
   * @return boolean          If it is allowed or not
   */
  private function isAllowedCommand($command) {
    if (preg_match('/^[a-zA-Z0-9 ]*$/', $command) && !in_array($command, self::$m_forbiddenCommands))
      return true;
    return false;
  }
};
?>