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

use \ ModuleBase;
use \ Nuwani;
use \ Nuwani \ Bot;
use \ LVPradio;
use \ UserStatus;

class Commands {
  public static function processCommand(Bot $bot, $command, $parameters, $channel, $nickname, $userLevel) {
    switch ($command) {

      case 'stopautodj':
        if ($channel != '#LVP.Radio')
          return CommandHelper::infoMessage($bot, $channel, 'This command is only usable in #LVP.Radio');

        if ($userLevel >= UserStatus::IsVoiced)
          self::onStopautodjCommand($bot, $channel, $parameters);
        else
          CommandHelper::errorMessage($bot, $channel, 'Only available for voiced users and above');
        return true;
    }

    return false;
  }

  // handles !stopautodj which sends the !autodj-force command to the radio bot
  private static function onStopautodjCommand(Bot $bot, $channel, $parameters, $userLevel) {
    if (RadioTracker::getAutoDJState() == false)
      return CommandHelper::infoMessage($bot, $channel, 'The autoDJ is not streaming. Ask the current DJ to stop streaming.');

    $bot->send('PRIVMSG LVP_Radio :!autodj-force');

    return true;
  }
};
