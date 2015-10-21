<?php
  /**
   * Configuration: Mineground
   *
   * Holds all confirgurations regarding the Mineground module
   *
   *      PermissionsFile: Where the bukkit plugin 'PermissionsEx' stores its data
   *      PermissionsBackupFirectory: Where the backups should be stored
   *      TombstonesDirectory: Where the bukkit tombstones files are located
   *      LowlightFile: Where the file containing suspicious
   */
  namespace Mineground;

  /**
   * Holds all configuration data and can return any value if requested
   *
   * @author Joeri de Graaf <Joeri@oostcoast.nl>
   */
  class Configuration {
    public static function value($entry) {
      $m_config =
        array(
          'CrewChannel'                   => '#LVP.Minecraft.Dev',
          'MinecraftServerPath'           => '/home/minecraft/server/',
          'MinecraftInitScript'           => '/etc/init.d/minecraft',

          'PermissionsFile'               => '/home/minecraft/testserver/plugins/PermissionsEx/permissions.yml',
          'PermissionsBackupDirectory'    => '/home/minecraft/testserver/plugins/PermissionsEx/Backup/',

          'WorldGuardReportFile'          => '/home/minecraft/testserver/plugins/WorldGuard/report.txt',

          'TombstonesDirectory'           => '/home/minecraft/testserver/plugins/Tombstone/',
          'LowLightFile'                  => '/home/minecraft/testserver/plugins/FoundDiamonds/log.txt',
          'EconomyFile'                   => '/home/minecraft/testserver/plugins/BOSEconomy/accounts.txt',
        );
      return $m_config[$entry];
    }


  };

?>
