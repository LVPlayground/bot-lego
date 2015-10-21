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

use \ ModuleBase;
use \ Nuwani;
use \ Mineground;
use \ UserStatus;

class Commands {
  const MESSAGE_MAX_LENGTH = 450;

  public static function processCommand(Bot $bot, $command, $parameters, $channel, $nickname, $userLevel) {
    switch ($command) {

      // Commands for anyone //
      case 'plugins':
        self::onPluginsCommand($bot, $channel);
        return true;

      case 'top10':
        self::onTop10Command($bot, $channel);
        return true;

      // Half-Operator commands //
      case 'tombstones':
        if ($userLevel >= UserStatus::IsHalfOperator)
          self::onTombstonesCommand($bot, $channel, $parameters);
        return true;

      case 'econ':
        if ($userLevel >= UserStatus::IsHalfOperator)
          self::onEconCommand($bot, $channel, $parameters);
        return true;

      // Operator commands //
      case 'gang':
        if ($userLevel >= UserStatus::IsOperator)
          self::onGangCommand($bot, $channel, $parameters, $userLevel);
        return true;

      case 'startserver':
        if ($userLevel >= UserStatus::IsOperator && $channel == Configuration::value('CrewChannel'))
          ServerController::startMinecraftServer($bot, $channel);
        return true;

      case 'stopserver':
        if ($userLevel >= UserStatus::IsOperator && $channel == Configuration::value('CrewChannel'))
          ServerController::stopMinecraftServer($bot, $channel);
        return true;

    }

    return false;
  }

  public static function processPrivateCommand(Bot $bot, $command, $parameters, $nickname) {
    return false;
  }

  private static function onGangCommand(Bot $bot, $channel, $parameters, $userLevel) {
    $subCommand = $parameters[0];
    $parameters = array_slice($parameters, 1);
    switch ($subCommand) {
      case 'create':
        self::onGangCreateCommand($bot, $channel, $parameters);
        return true;
      case 'remove':
        self::onGangRemoveCommand($bot, $channel, $parameters);
        return true;
      case 'list':
        self::onGangListCommand($bot, $channel, $parameters, $userLevel);
        return true;
      case 'members':
        self::onGangMembersCommand($bot, $channel, $parameters, $userLevel);
        return true;
      default:
        CommandHelper::usageMessage($bot, $channel, '!gang [create/remove/members/list (all)]');
        return true;
    }
  }

  /**
   * Handles the !gang create command
   *
   * @param array   $parameters  All parameters that were passed through the command
   * @param string  $channel     The channel the command originated from
   * @param string  $nickname    Name of the user that send the command
   */
  private static function onGangCreateCommand(Bot $bot, $channel, $parameters) {
    if (count($parameters) != 2) {
      CommandHelper::usageMessage($bot, $channel, '!gang create [tag] [color]');
      return true;
    }
    if (!self::isValidGangName($parameters[0])) {
      return CommandHelper::errorMessage($bot, $channel,
        'a gangtag must be 3 to 10 characters long and only contain alphanumerical characters and \'-\'');
    }
    if (!PermissionsHandler::isValidMinecraftColorName($parameters[1])) {
      CommandHelper::errorMessage($bot, $channel, 'Invalid color. Availible colors:');
      $message = implode(', ', array_map('ucfirst', array_keys(PermissionsHandler::$minecraftColorNames))) . '. See http://bit.ly/1abuBSj';
      CommandHelper::longMessage($bot, $channel, $message);
      return true;;
    }
    if(PermissionsHandler::createGang($bot, $channel, $parameters[0], strtolower($parameters[1])))
      CommandHelper::successMessage($bot, $channel,  'gang ' . $parameters[0] . ' has been created');

    return true;
  }

  /**
   * Handles the !gang remove command
   *
   * @param array   $parameters  All parameters that were passed through the command
   * @param string  $channel     The channel the command originated from
   * @param string  $nickname    Name of the user that send the command
   */
  private static function onGangRemoveCommand(Bot $bot, $channel, $parameters) {
    if (count($parameters) != 1){
      CommandHelper::usageMessage($bot, $channel, '!gang remove [tag]');
        return true;
    }
    if (!self::isValidGangName($parameters[0])) {
      return CommandHelper::errorMessage($bot, $channel, 'a gangtag may only contain alphanumerical characters and \'-\'');
    }
    PermissionsHandler::removeGang($bot, $channel, $parameters[0]);
  }

  /**
   * Handles the !gang list command
   *
   * @param array   $parameters  All parameters that were passed through the command
   */
  private static function onGangListCommand(Bot $bot, $channel, $parameters, $userLevel) {
    if (isset($parameters[0]) && $parameters[0] == 'all'){
      if($userLevel >= UserStatus::IsProtected)
        PermissionsHandler::listGangs($bot, $channel, false);
      else
        return CommandHelper::errorMessage($bot, $channel, 'only Managers may use this command.');
    }
    else
      PermissionsHandler::listGangs($bot, $channel, true);
    return true;
  }

  /**
   * Handles the !gang member command. Will return a list of all members of that group.
   *
   * @param   Array $parameters All parameters that were passed to the bot
   */
  private static function onGangMembersCommand(Bot $bot, $channel, $parameters, $userLevel) {
    if (count($parameters) < 1)
      return CommandHelper::usageMessage($bot, $channel, '!gang members [gang]');

    if (!self::isValidGangName($parameters[0]))
      return CommandHelper::errorMessage($bot, $channel, 'Invalid gang name');

    if ($parameters[1] == 'all' && ($parameters[0] == 'Admin' || $parameters[0] == 'Mod' || $parameters[0] == 'Management')
      && $userLevel >= UserStatus::IsProtected)
      $groupMembers = PermissionsHandler::listGroupMembers($parameters[0], true);
    else {
      if (PermissionsHandler::isHiddenGroup($parameters[0]) && $parameters[0] != 'Admin' && $parameters[0] != 'Mod' && $parameters[0] != 'Management')
        return CommandHelper::errorMessage($bot, $channel, 'that group can not be shown');

      $groupMembers = PermissionsHandler::listGroupMembers($parameters[0]);
    }

    if (!$groupMembers)
      return CommandHelper::infoMessage($bot, $channel, $parameters[0] . ' has no members');

    if (array_key_exists($parameters[0], $groupMembers)){
      $regularMembers = array_keys($groupMembers[$parameters[0]]);

      if (count($regularMembers) == 1)
        $regularMessage = ModuleBase::CLEAR . 'one member: ' . ModuleBase::COLOUR_TEAL . implode(', ', $regularMembers);
      else
        $regularMessage = ModuleBase::CLEAR . count($regularMembers) . ' members: ' . ModuleBase::COLOUR_TEAL . implode(', ', $regularMembers);
    }
    if (array_key_exists($parameters[0] . '+vip', $groupMembers)){
      $vipMembers = array_keys($groupMembers[$parameters[0]. '+vip']);

      if (count($regularMembers) == 1)
        $vipMessage = ModuleBase::CLEAR . 'one vip member: '  . ModuleBase::COLOUR_TEAL . implode(', ', $regularMembers);
      else
        $vipMessage = ModuleBase::CLEAR . count($vipMembers) . ' vip members: '  . ModuleBase::COLOUR_TEAL . implode(', ', $vipMembers);
    }

    if (count($vipMembers) && count($regularMembers))
      $message = $regularMessage . ModuleBase::CLEAR .  ' and ' . $vipMessage;
    else if ($vipMembers && !count($regularMembers))
      $message = $vipMessage;
    else
      $message = $regularMessage;

    CommandHelper::longMessage($bot, $channel, ModuleBase::BOLD . ModuleBase::COLOUR_TEAL . '\'' . $parameters[0] . ModuleBase::CLEAR . '\' has ' . $message);


    // TODO: implode members into strings and output
    // TODO: implement override in listgroupmembers to only list top level, not hidden admin/mod status. Everything in $hiddengroups basically
  }

  /**
   * Handles the !tombstones command
   *
   * @param  Bot    $bot
   * @param  string $channel
   * @param  array $parameters
   */
  private static function onTombstonesCommand(Bot $bot, $channel, $parameters) {
    if (count($parameters) != 1)
      return CommandHelper::usageMessage($bot, $channel, '!tombstones [player/world]. Availible worlds: ' . implode(', ', TombstoneHandler::listTombstonesWorlds()));

    // If a world was specified, list all tombstones in that world
    if (TombstoneHandler::isTombstonesWorld($parameters[0])) {
      $tombstoneList = TombstoneHandler::listTombstonesInWorld($parameters[0]);

      if (!$tombstoneList)
        return CommandHelper::infoMessage($bot, $channel, 'There are no tombstones in ' . $parameters[0]);

      CommandHelper::infoMessage($bot, $channel, 'Tombstones in world ' . $parameters[0] . ' :');

      foreach ($tombstoneList[$parameters[0]] as $tombstoneOwner => $tombstones) {
        foreach ($tombstoneList[$parameters[0]][$tombstoneOwner] as $tombstoneData)
          $bot->send('PRIVMSG ' . $channel . ' :' . ModuleBase::COLOUR_TEAL . $tombstoneOwner . ModuleBase::CLEAR . ' at x:'
           . $tombstoneData[0] . ' y:' . $tombstoneData[1] . ' z:' . $tombstoneData[2] . ' and will be destroyed at ' . date('H:i:s' , $tombstoneData[3]));
      }

      return true;
    }

    // If a player name was specified, list all tomstones for that player in each world
    $tombstoneListForPlayer = TombstoneHandler::listTombstonesForPlayer($parameters[0]);
    if ($tombstoneListForPlayer == NULL)
      return CommandHelper::errorMessage($bot, $channel, 'Invalid player/world');

    foreach ($tombstoneListForPlayer as $tombstoneWorld => $tombstones) {
      foreach ($tombstoneListForPlayer[$tombstoneWorld] as $tombstoneData)
        $bot->send('PRIVMSG ' . $channel . ' :' . 'Tombstone found in '. ModuleBase::COLOUR_TEAL . $tombstoneWorld . ModuleBase::CLEAR . ' x:' . $tombstoneData[0] .
          ' y:' . $tombstoneData[1] . ' z:' . $tombstoneData[2] . ' and will be destroyed at ' . date('H:i:s' , $tombstoneData[3]));

      return true;
    }
  }

  /**
   * Handles !plugins
   *
   * @param  Bot    $bot     Bot array
   * @param  string $channel Channel where the command originated from
   */
  private static function onPluginsCommand(Bot $bot, $channel) {
    #ServerController::sendCommandToServer('worldguard report');
    $pluginCommandTimer = new Nuwani\Timer();
    $pluginCommandTimer->create(Commands::onPluginTimerEnd($bot, $channel), 1000);
  }

  /**
   * Gets called once the plugin timer is done. The Minecraft server needs a couple of miliseconds to generate the file we need
   *
   * @param  Bot    $bot     Bot array
   * @param  string $channel Channel the command originated from
   */
  private static function onPluginTimerEnd(Bot $bot, $channel) {
    $worldGuardReportFile = Configuration::value('WorldGuardReportFile');

    if (!file_exists($worldGuardReportFile))
      CommandHelper::errorMessage($bot, $channel, 'The WorldGuard report could not be loaded');

    $worldGuardReport = file_get_contents($worldGuardReportFile);

    // Graps all the plugin content from the file
    $pluginData = preg_replace('/^.*Plugins \(\d{1,}\)\r\n-{12}\r\n\r\n(.*)\r\n\r\n-{6}\r\n.*$/s', '$1', $worldGuardReport);

    // Strips out all relevant data
    preg_match_all('/(\w{1,})\s{1,}:\s(\w.\w)/', $pluginData, $pluginMatches);

    $pluginList = array();

    foreach ($pluginMatches[1] as $key  => $value) {
      $pluginList[$value] = $pluginMatches[2][$key];
    }

    self::natksort($pluginList);

    CommandHelper::infoMessage($bot, $channel, 'Plugins on LVM (' . sizeof($pluginList) . '):');

    $plugins = array();

    foreach ($pluginList as $pluginName => $pluginVersion) {
      $plugins[] = ModuleBase::COLOUR_TEAL . $pluginName . ModuleBase::CLEAR . ' (' . $pluginVersion . ')';
    }

    $message = implode(', ', $plugins);

    return CommandHelper::longMessage($bot, $channel, $message);
  }

  /**
   * Handles !top10
   */
  private static function onTop10Command(Bot $bot, $channel) {
    EconHandler::loadAccounts();
    $wealthiestPlayers = EconHandler::ListWealthiestPlayers(10);

    if (!$wealthiestPlayers)
      return CommandHelper::errorMessage($bot, $channel, 'the economy file could not be loaded/parsed');

    CommandHelper::infoMessage($bot, $channel, 'the top 10 wealthiest players on LVM');

    foreach ($wealthiestPlayers as $playerName => $econValue) {
      $bot -> send ('PRIVMSG ' . $channel . ' :' . ModuleBase::COLOUR_GREEN . $playerName . ' - ' . ModuleBase::CLEAR . $econValue);
    }
  }

  /**
   * Handles !econ [playerName]
   */
  private static function onEconCommand(Bot $bot, $channel, $parameters) {
    if (count($parameters) != 1)
      return CommandHelper::usageMessage($bot, $channel, '!econ [player]');
    if (!self::isValidNickname($parameters[0]))
      return CommandHelper::errorMessage($bot, $channel, 'Invalid player name');

    EconHandler::loadAccounts();
    $econAmount = EconHandler::getPlayerEconAmount($parameters[0]);

    if ($econAmount === false)
      return CommandHelper::errorMessage($bot, $channel, 'Player not found');
    else if ($econAmount == 0)
      return CommandHelper::infoMessage($bot, $channel, $parameters[0] . ' has not a single LVM dollar');
    else
      return CommandHelper::infoMessage($bot, $channel, $parameters[0] . ' has ' . ModuleBase::COLOUR_GREEN . $econAmount . ModuleBase::CLEAR . ' LVM dollars');
  }

  // Utility function to validate a gangname
  private static function isValidGangName($gangName) {
    return preg_match('/^[a-zA-Z0-9\-]{2,10}$/', $gangName);
  }

  // Utility function to validate a nickname.
  private static function isValidNickname($nickname) {
    return preg_match('/^[A-Za-z0-9\[\]\.\$\=\@\(\)_]{3,23}$/', $nickname);
  }

  // Utility to ksort naturally
  private static function natksort(&$array) {
    $keys = array_keys($array);
    natcasesort($keys);

    foreach ($keys as $k) {
        $new_array[$k] = $array[$k];
    }

    $array = $new_array;
    return true;
  }
};
