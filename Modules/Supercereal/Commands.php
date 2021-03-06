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

use \ Nuwani;
use \ Nuwani \ Bot;
use \ ModuleBase;
use \ UserStatus;
use \ Supercereal;

class Commands {
  const MESSAGE_MAX_LENGTH = 450;
  
  //TODO: expand the blacklist
  private static $m_serialBlackList = array(
      "EEACC9DA0D4E9E5EEFEF55C890489095090DD8AF",
      "F8CF9F4E9DECC554C89CDD05E4FA9C4D48FD08C5",
      "4945DECED9CAE99DC5E9449E00898D8DCEAD5CD4",
      "C4EE5AAE444859EDDEC8C999CE98DAD4ED80DAFE",
      "AC90E8CECFF88CE8D5C884E8DCCE804909EDD5A9",
      "CFDADC8489FDEEAFEE80C4AD5D8EE4FEDEEE5548",
      "DDE48048E9DD8899C40DE4A84084504EDD0CA494",
      );

  public static function processCommand(Bot $bot, $command, $parameters, $channel, $nickname, $userLevel) {
    $channel = strtolower($channel);

    switch ($command) {

      // Operator commands //
      case 'banserial':
        if ($userLevel >= UserStatus::IsOperator && in_array($channel, Supercereal::CrewChannels))
          self::onBanSerialCommand($bot, $channel, $parameters, $nickname);
        return true;

      case 'unbanserial':
        if ($userLevel >= UserStatus::IsOperator && in_array($channel, Supercereal::CrewChannels))
          self::onUnbanSerialCommand($bot, $channel, $parameters);
        return true;

      case 'isserialbanned':
        if ($userLevel >= UserStatus::IsOperator && in_array($channel, Supercereal::CrewChannels))
          self::onIsSerialBannedCommand($bot, $channel, $parameters);
        return true;

      case 'serialinfo':
        if ($userLevel >= UserStatus::IsOperator && in_array($channel, Supercereal::CrewChannels))
          self::onSerialInfoCommand($bot, $channel, $parameters);
        return true;

      // Protected commands //
      case 'reloadserialbanlist':
        if ($userLevel >= UserStatus::IsProtected && in_array($channel, Supercereal::CrewChannels))
          self::onReloadserialbanlistCommand($bot, $channel);
        return true;
    }

    return false;
  }

  // Handles !banserial
  private static function onBanSerialCommand(Bot $bot, $channel, $parameters, $issuer) {
    if (count($parameters) < 2)
      return CommandHelper::usageMessage($bot, $channel, '!banserial [serial] [player name]');

    // Check whether the serial is faulty
    if (!self::isValidSerial($parameters[0]))
      return CommandHelper::errorMessage($bot, $channel, 'The supplied serial is not valid.');

    // Check if the serial is too common and probably not unique
    if (in_array($parameters[0], self::$m_serialBlackList))
      return CommandHelper::infoMessage($bot, $channel, 'This serial is too common to ban. Use other methods.');

    $serialBanData = BanManager::isSerialBanned($parameters[0]);
    
    // Check if the serial is already banned
    if ($serialBanData !== false)
      return CommandHelper::infoMessage($bot, $channel, "This serial is already banned for: '$serialBanData[0]' by '$serialBanData[1]' on " . date('j/n/Y G:i:s', $serialBanData[2]));
    
    // Ban the serial
    if (BanManager::addSerialToBanlist($parameters[0], $parameters[1], $issuer)) {
      CommandHelper::successMessage($bot, $channel, 'Serial banned');
    }
    else
      CommandHelper::errorMessage($bot, $channel, 'Something went wrong. Notify Joeri');

  }

  // Handles !unbanserial
  private static function onUnbanSerialCommand(Bot $bot, $channel, $parameters) {
    if (count($parameters) < 1)
      return CommandHelper::usageMessage($bot, $channel, '!unbanserial [serial]');

    // Check whether the serial is faulty
    if (!self::isValidSerial($parameters[0]))
      return CommandHelper::errorMessage($bot, $channel, 'The supplied serial is not valid.');

    // Check if the serial is banned
    if (BanManager::isSerialBanned($parameters[0])){
      if (BanManager::removeSerialFromBanlist($parameters[0])){
        CommandHelper::successMessage($bot, $channel, 'Serial unbanned!');
      }
      else
        CommandHelper::errorMessage($bot, $channel, 'Something went wrong. Notify Joeri');
    }
    else
      CommandHelper::infoMessage($bot, $channel, 'The serial is not banned');
  }

  // Handles !isserialbanned
  private static function onIsSerialBannedCommand(Bot $bot, $channel, $parameters) {
    if (count($parameters) < 1)
      return CommandHelper::usageMessage($bot, $channel, '!isserialbanned [serial]');

    // Check whether the serial is faulty
    if (!self::isValidSerial($parameters[0]))
      return CommandHelper::errorMessage($bot, $channel, 'The supplied serial is not valid.');

    // Check if the serial exists in the BanManager
    $serialBanData = BanManager::isSerialBanned($parameters[0]);

    if ($serialBanData == false)
      CommandHelper::infoMessage($bot, $channel, 'The serial is not banned');
    else
      CommandHelper::infoMessage($bot, $channel, "This serial is banned for: '$serialBanData[0]' by '$serialBanData[1]' on " . date('j/n/Y G:i:s', $serialBanData[2]));
      
  }

  // Handles !reloadserialbanlist
  private static function onReloadserialbanlistCommand(Bot $bot, $channel) {
    BanManager::loadBanList();
    CommandHelper::infoMessage($bot, $channel, 'reloaded the serial ban list');
  }

  // Handles !serialinfo
  private static function onSerialInfoCommand(Bot $bot, $channel, $parameters) {
    if (count($parameters) < 1)
      return CommandHelper::usageMessage($bot, $channel, '!serialinfo [Name/IP/serial] [all]');

    // Import the file and slice it up
    if (!file_exists(SerialMonitor::$m_serialLogFile))
      return CommandHelper::errorMessage($bot, $channel, 'The serial database was not found');

    // Check if the parameter is an IP
    if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $parameters[0])){
      $serialList = array();
      $output = array();

      $results = self::getGrepResults($parameters[0]);

      foreach ($results as $result) {
        if ($result[1] != $parameters[0]) continue;
        $serialList[] = $result[2];
      }

      $serialList = array_count_values($serialList);

      foreach ($serialList as $serial => $matches) {
        $output[] = $serial . ModuleBase::COLOUR_DARKGREY . ' (' . $matches . 'x)' . ModuleBase::CLEAR;
      }

      if (!count($serialList))
        return CommandHelper::infoMessage($bot, $channel, 'No serials found');
      else
        return CommandHelper::longMessage($bot, $channel, ModuleBase::COLOUR_PINK . 'Serials found: ' . ModuleBase::CLEAR . implode(', ', $output));
    }

    // Or an serial
    else if (preg_match('/^[A-Z0-9]{40}$/', $parameters[0])) {
      $serialBanData = BanManager::isSerialBanned($parameters[0]);

      // Notify the issuer that this serial has been banned
      if ($serialBanData !== false)
        CommandHelper::infoMessage($bot, $channel, "This serial is banned for: '$serialBanData[0]' by '$serialBanData[1]' on " . date('j/n/Y G:i:s', $serialBanData[2]));
      
      if (in_array($parameters[0], self::$m_serialBlackList))
        return CommandHelper::infoMessage($bot, $channel, 'This serial is too common for banning or tracking purposes. Use other methods');
      
      $serialList = array();
      $output = array();

      $results = self::getGrepResults($parameters[0]);
      $limit = 20;

      foreach ($results as $result) {
        if ($result[2] != $parameters[0]) continue;
        $serialList[] = $result[0];
      }

      $count = count($serialList);

      $serialList = array_count_values($serialList);

      if ($count > 20) {
        if ($parameters[1] == 'all')
          $limit = 100;

          $serialList = array_slice($serialList, 0, $limit);

          if (count($results) > $limit)
            $serialList[] = (count($results)-$limit) . ' more';
      }

      foreach ($serialList as $serial => $matches) {
        $output[] = $serial . ModuleBase::COLOUR_DARKGREY . ' (' . $matches . 'x)' . ModuleBase::CLEAR;
      }
      
      if (!count($serialList))
        return CommandHelper::infoMessage($bot, $channel, 'No serials found');
      else
        return CommandHelper::longMessage($bot, $channel, ModuleBase::COLOUR_PINK . 'Players found: ' . ModuleBase::CLEAR . implode(', ', $output));
    }

    // It could also be a player name
    else if (preg_match('/^.{3,24}$/', $parameters[0])) {
      $serialList = array();
      $output = array();

      $results = self::getGrepResults($parameters[0]);

      foreach ($results as $result) {
        if ($result[0] != $parameters[0]) continue;
        $serialList[] = $result[2];
      }

      $serialList = array_count_values($serialList);

      foreach ($serialList as $serial => $matches) {
        $output[] = $serial . ModuleBase::COLOUR_DARKGREY . ' (' . $matches . 'x)' . ModuleBase::CLEAR;
      }

      if (!count($serialList))
        return CommandHelper::infoMessage($bot, $channel, 'No serials found');
      else
        return CommandHelper::longMessage($bot, $channel, ModuleBase::COLOUR_PINK . 'Serials found: ' . ModuleBase::CLEAR . implode(', ', $output));
    }
    else
      return CommandHelper::errorMessage($bot, $channel, 'Invalid query');
  }

  // Utility function to search a multi dimensional array for a given string and returns the key(s)
  private static function searchArrayForValue($needle, $haystack) {
    $results = array();
    foreach ($haystack as $key => $array) {
      $match = array_search($needle, $array);
      if ($match !== FALSE) {
        $results[] = $key;
      }
    }

    if (empty($results))
      return false;

    return $results;
  }
  // Utility function to validate a serial
  private static function isValidSerial($serial) {
    return preg_match('/^[A-Z0-9]{40}$/', $serial);
  }

  // Utility function to fetch an array with results from through a grep on the serial log
  private static function getGrepResults($needle) {
    $grepResult = shell_exec('grep -iF ' . escapeshellarg($needle) . ' ' . SerialMonitor::$m_serialLogFile);
    $grepResults = explode("\r\n", $grepResult);

    $results = array();

    foreach ($grepResults as $key => $grepResult) {
      if (!preg_match("/^(.{3,24}),-?\d+,(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}),([A-Z0-9]{40})$/", $grepResult, $grepResults)) continue;

      $results[] = array($grepResults[1], $grepResults[2], $grepResults[3]);
    }

      return $results;
  }
};
