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

use \ Nuwani;

require_once __DIR__ . '/yaml/Yaml.php';

use \ Yaml;

use Mineground \ Configuration;

/**
 * handles all permission based operations
 *
 * @author  Joeri de Graaf <joeri@oostcoast.nl>
 */
class PermissionsHandler {  
  public static $minecraftColorNames = array (
                                          'black' =>  '0',
                                          'navy' =>   '1',
                                          'green' =>  '2',
                                          'teal' =>   '3',
                                          'maroon' => '4',
                                          'purple' => '5',
                                          'gold' =>   '6',
                                          'silver' => '7',
                                          'grey' =>   '8',
                                          'blue' =>   '9',
                                          'lime' =>   'a',
                                          'aqua' =>   'b',
                                          'red' =>    'c',
                                          'pink' =>   'd',
                                          'yellow' => 'e',
                                          'white' =>  'f');
  private static $hiddenGroups = array ('Noob', 'Builder', 'SBuilder', 'VIP', 'Dev', 'Mod', 'Admin', 'Management');

  /**
   * Holds the contents of the permissions file once loaded into memory
   *
   * @var array
   */
  private static $m_permissions = array();

 /**
   * Adds a gang to the permissions file
   *
   * @param string Name of the gang to be created
   * @param string Name of the color to give the gang
   */
  public static function createGang(Bot $bot, $channel, $gangName, $colorName) {
    if (!self::loadPermissions())
      return false;

    $gangColor = self::convertToMinecraftColor($colorName);
    
    if (array_key_exists($gangName, self::$m_permissions['groups'])){
      CommandHelper::errorMessage($bot, $channel, 'that gang already exist');
      return false;
    }
    
    $gangArray = array ($gangName => array (
                  'inheritance' => array (0 => 'SBuilder',),
                  'worlds' => array (
                    'LVM' => array ('prefix' => '[' . $gangColor . $gangName . '&f] ',),
                    'LVM_the_end' => array ('prefix' => '[' . $gangColor . $gangName . '&f] ',),
                    'LVM_nether' => array ('prefix' => '[' . $gangColor . $gangName . '&f-&4N&f] ',),
                    'classic' => array ('prefix' => '[' . $gangColor . $gangName . '&f&dC&f] ',),
                    'survivaltown' => array ('prefix' => '[&6Hunger&f] ',),
                    'Event_world' => array ('prefix' => '[&aEvent&f] ',),
                    'Kurow_Island' => array ('prefix' => '[&6Hunger&f] ',),
                  ),
                ),
                $gangName . '+vip' => array (
                  'inheritance' => array (0 => 'VIP',),
                  'worlds' => array (
                    'LVM' => array ('prefix' => '[' . $gangColor . $gangName . '&f] ',),
                    'LVM_the_end' => array ('prefix' => '[' . $gangColor . $gangName . '&f] ',),
                    'LVM_nether' => array ('prefix' => '[' . $gangColor . $gangName . '&f-&4N&f] ',),
                    'classic' => array ('prefix' => '[' . $gangColor . $gangName . '&f&dC&f] ',),
                    'survivaltown' => array ('prefix' => '[&6Hunger&f] ',),
                    'Event_world' => array ('prefix' => '[&aEvent&f] ',),
                    'Kurow_Island' => array ('prefix' => '[&6Hunger&f] ',),
                  ),
                ),
              );

    self::$m_permissions['groups'] += $gangArray;

    if(!self::savePermissions())
      return false;

    ServerController::sendCommandToServer('pex reload');

    return true;
  }

  /**
   * Removes a gang from the permissions file
   *
   * @param string Name of the gang to be removed
   *
   */
  public static function removeGang(Bot $bot, $channel, $gangName) {
    if(in_array($gangName, self::$hiddenGroups)){
      return CommandHelper::errorMessage($bot, $channel, 'only gangs can be removed, stay away from the usergroups');
    }

    $gangMembers = self::listGroupMembers($gangName);

    if (!array_key_exists($gangName, self::$m_permissions['groups']))
      return CommandHelper::errorMessage($bot, $channel, 'that gang doesn\'t exist, see !gang list');

    // Lets see if there are people in that gang, if so, move them to SBuilder or VIP
    if (array_key_exists($gangName, $gangMembers) || array_key_exists($gangName . '+vip', $gangName)) {
      self::moveGroupMembers($gangName);
      $movedMemberCount = count($gangMembers[$gangName]) + count($gangMembers[$gangName . '+vip']);

      if ($movedMemberCount == 1)
        CommandHelper::infoMessage($bot, $channel, 'the only member of ' . $gangName . ' has been moved to the default group');
      else
        CommandHelper::infoMessage($bot, $channel, 'the ' . $movedMemberCount . ' members of ' . $gangName . ' have been moved to the default groups');

      self::moveGroupMembers($gangName);
    }

    unset(self::$m_permissions['groups'][$gangName]);
    unset(self::$m_permissions['groups'][$gangName . '+vip']);

    if(!self::savePermissions())
      return CommandHelper::errorMessage($bot, $channel, 'something went wrong during saving');

    ServerController::sendCommandToServer('pex reload');
    return true;
  }

  /**
   * Will move all members of the gang specified to the default usergroups or optionally, to another group.
   *
   * @param  string  $oldGroupName  Name of the gang to move the members off
   * @param  string   $newGroupName Name of the gang to move to
   *
   * @return boolean                If the members were successfully moved
   */
  public static function moveGroupMembers($oldGroupName, $newGroupName = false) {
    $groupMembers = self::listGroupMembers($oldGroupName);

    if (!$groupMembers)
      return false;

    if (in_array($newGroupName, self::$hiddenGroups))
      return false;

    if (!$newGroupName) {
      $RegularGroupName = 'SBuilder';
      $VipGroupName = 'VIP';
    }
    else {
      $RegularGroupName = $newGroupName;
      $VipGroupName =   $newGroupName . '+vip';
    }

    if (array_key_exists($oldGroupName, $groupMembers))
      foreach ($groupMembers[$oldGroupName] as $regularMember => $groupKey)
          self::$m_permissions['users'][$regularMember]['group'][$groupKey] = $RegularGroupName;

    if(array_key_exists($oldGroupName . '+vip', $groupMembers))
      foreach ($groupMembers[$oldGroupName . '+vip'] as $vipMember => $groupKey)
        self::$m_permissions['users'][$vipMember]['group'][$groupKey] = $VipGroupName;
    return true;
  }

  /**
   * Returns an array containing all members of the specified group
   *
   * Example return:
   *
   * array (
   *   'GroupName' => array (
   *      'GroupMember1 => 'keyOfMatch',
   *      'GroupMember2 => 'keyOfMatch',
   *    )
   *    'GroupName+vip' => array (
   *      vipGroupMember1 => 'keyOfMatch',
   *    ) 
   *)
   *
   * The keyOfMatch is the cell in side the permissions array that matched to the group. 
   * This is necessary since people can be in multiple groups at once and we don't want
   * to change the order.
   *
   * @param  string $groupName Name of the group to check
   *
   * @return array             The list of members, vip's are in the vip key, regular member in the regular key.
   */
  public static function listGroupMembers($groupName, $showHiddenCrewStatus = false) {
    if (!self::loadPermissions())
      return false;
    if(!self::doesGangExist($groupName))
      return false; 

    $groupMembers = array();

    foreach (self::$m_permissions['users'] as $user => $properties) {
      // To verify that an group is actually set
      if (!array_key_exists('group', $properties))
        continue;
      if ($properties['group'] == NULL)
        continue;

      $regularMatch = array_search($groupName, $properties['group']);
      $vipMatch = array_search($groupName . '+vip', $properties['group']);

      if (!$showHiddenCrewStatus && in_array($groupName, self::$hiddenGroups) && $regularMatch != 0)
        $regularMatch = false;


      if ($regularMatch !== false) 
        $groupMembers[$groupName][$user] = $regularMatch;
      else if ($vipMatch !== false && self::doesGangExist($groupName . '+vip'))
        $groupMembers[$groupName . '+vip'][$user] = $vipMatch;
      else
        continue;
    }
    return $groupMembers;
  }

  /**
   * Lists all gangs in the permissions file
   * 
   * Has an option to hide all regular groups which are not gangs
   *
   * @param string  $channel name of the channel the command originated from
   * @param boolean $hideNonGangs If regular user groups have to be hidden
   *
   * @return Send the list to the user on IRC
   */
  public static function listGangs(Bot $bot, $channel, $hideNonGangs = true) {
    if(!self::loadPermissions()){
      CommandHelper::errorMessage($bot, $channel, 'the permissions could not be loaded');
      return false;
    }

    $groupsToRemove = array();

    $groups = array_keys(self::$m_permissions['groups']);
    
    if ($hideNonGangs){
      $groupsToRemove = self::$hiddenGroups;
      
      foreach ($groups as $group) {
        if (in_array($group . '+vip', $groups))
          $groupsToRemove[] = $group . '+vip';
      }
    }
    $groups = array_diff($groups, $groupsToRemove);
    natcasesort($groups);
    $output = implode(', ', $groups);
    $output = explode(PHP_EOL, wordwrap($output, 400));
    
    CommandHelper::infoMessage($bot, $channel, 'Gangs on LVM:');

    foreach ($output as $line) {
      $bot->send('PRIVMSG ' . $channel . ' :' . $line);
    }

    return true;
  }

  /**
   * Loads the current Yaml permissions file into memory 
   */
  private static function loadPermissions() {
    self::$m_permissions = array();
    if (file_exists(Configuration::value('PermissionsFile'))) {
      self::$m_permissions = Yaml\Yaml::parse(Configuration::value('PermissionsFile'));
      return true;
    }
    else
      return false;
  }

  /**
   * Saves the given permission data to file and makes a backup from the last one
   *
   * @return boolean   If saving was succesful
   */
  private function savePermissions() {
    if (file_exists(Configuration::value('PermissionsFile'))) {
      if (is_dir(Configuration::value('PermissionsBackupDirectory'))) {
        if(!copy(Configuration::value('PermissionsFile'), Configuration::value('PermissionsBackupDirectory') . 'permissions_' . 
          date("d-m-y_H-i-s") . '.yml'))
          return false;
      }
      else
        return false;
    }
    $output = Yaml\Yaml::dump(self::$m_permissions, 10, 2);
    if(!file_put_contents(Configuration::value('PermissionsFile'), $output))
      return false;
    
    return true;
  }

  // Utility function to check whether a group exists
  public static function doesGangExist($gangName) {
    return array_key_exists($gangName, self::$m_permissions['groups']);
  } 

  // Utility function to test whether the provided string is a valid minecraft color
  public static function isValidMinecraftColorName($colorName) {
    return array_key_exists(strtolower($colorName), self::$minecraftColorNames);
  }

  // Utility function to return a valid minecraft color code
  private static function convertToMinecraftColor($colorName) {
    return '§' . self::$minecraftColorNames[$colorName];
  }

  // Utility function to check if a groupname may be used
  public static function isHiddenGroup($groupName) {
    return in_array($groupName, self::$hiddenGroups);
  }
};