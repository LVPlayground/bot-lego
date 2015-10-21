<?php
/**
 * Nuwani PHP IRC Bot Framework
 * Copyright (c) 2006-2010 The Nuwani Project
 *
 * Nuwani is a framework for IRC Bots built using PHP. Nuwani speeds up bot
 * development by handling basic tasks as connection- and bot management, timers
 * and module managing. Features for your bot can easily be added by creating
 * your own modules, which will receive callbacks from the framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) 2006-2010 The Nuwani Project
 * @package Nuwani
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik.grapendaal@gmail.com>
 * @see http://nuwani.googlecode.com
 */

$aConfiguration = array
(
        /**
         * Configuration: Networks
         *
         * The networks define the servers which can be used for which network. To allow
         * automated load balancing for the IRC Network, multiple servers should be
         * defined rather than just one. Round robin is supported, but not advisable.
         */

        'Networks' => array
        (
                'GTANet' => array
                (
                        'irc.gtanet.com',
                )
        ),

        /**
         * Configuration: Bots
         *
         * Which bots should be automatically spawned? Each bot got their nickname,
         * username and realname to adjust. Network specified the network to connect
         * with, and Auto* describe commands and actions to activate on join.
         *
         * For the SSL key; use the false boolean to disable SSL support. Otherwise
         * enter the port number (an integer!) to identify the SSL port.
         */

        'Bots' => array
        (
                array
                (
                        'Nickname'      => 'Brick',
                        'AltNickname'   => 'Bricked',
                        'Username'      => 'Lego',
                        'Realname'      => '',

                        'Network'       => 'GTANet',

                        'BindIP'        => 'server-ip-goes-here',
                        'SSL'           => false,

                        'Slave'         => false,
                        'OnConnect'     => array
                        (
                                // Channels to auto-join
                                'Channels' => array
                                (
                                        '#yourchannel'
                                ),

                                // And commands executed on auto-join
                                'PRIVMSG NickServ :IDENTIFY the-bots-irc-password'

                        ),

                        /**
                         * Configuration: Users
                         *
                         * This array is an configuration option which defines people on IRC who have
                         * elevated rights.
                         *
                         * The user masks are 1:1. They are used to identify one user, and only one user mask can match
                         * for a user. Overlapping user masks does not give an error, but its behavior is undefined.
                         *
                         * - UserMask: The full user mask as it appears on the IRC network this bot is configured for.
                         *             Wildcards are supported for any part of the usermask, however partial matching
                         *             will not work.
                         *             Valid: *!username@hostname.com
                         *             Invalid: nick*!username@hostname.com
                         * - Password: MD5 hash (no salt) of the user's password. Empty or null means no password is
                         *             required for the user to log in.
                         * - Permissions: Array of permissions this user is granted. Permissions are simply strings.
                         *             Nuwani only supports 'owner' out of the box.
                         */

                        'Users' => array
                        (
                                array
                                (
                                        'UserMask'      => 'nickname!username@hostname.com',
                                        'Password'      => 'md5-hash-of-your-password',
                                        'Permissions'   => array
                                        (
                                                'owner', 'evaluation'
                                        )
                                )
                        ),

                        'QuitMessage'   => 'Fell apart'
                )
        ),

        /**
         * Configuration: Owners
         * Module: Evaluation
         *
         * This array is an configuration option specifically intended for the
         * Evaluation module, and defines the prefix to use when trying to evaluate
         * PHP code from IRC.
         */

        'Owners' => array
        (
                'Prefix'        => 'Lego:',

                array
                (
                        'Username'      => 'nickname!username@hostname.com',
                        'Password'       => 'md5-hash-of-your-password',
                        'Identified'    => true
                ),
        ),

        /**
         * Configuration: PriorityQueue
         *
         * This simple array defines what modules should be called before any other
         * module when processing events. This could be used for modules which ignore
         * users or commands or even modules which track stuff. The order given in
         * this array will be maintained. The rest of the modules will be ordered by
         * alphabet.
         */

        'PriorityQueue' => array
        (

        ),

        /**
         * Configuration: MySQL
         *
         * These options define the MySQL connection information that various
         * modules of this bot can use. The hostname, username, password
         * and database directives are clear. The "Restart" option defines after
         * how many seconds the connection should be restarted.
         */

        'MySQL' => array
        (
                'enabled'       => false,
                'hostname'      => 'localhost',
                'username'      => 'root',
                'password'      => '',
                'database'      => '',
                'restart'       => 30
        ),

        /**
         * Configuration: ErrorHandling
         *
         * The error-handling level can be changed, which will effectively
         * change the number of errors which get displayed on the output and
         * the number of levels that get send to IRC. There are three
         * common levels you can choose from:
         *
         *     ErrorExceptionHandler :: ERROR_OUTPUT_SILENT (deprecated)
         *     ErrorExceptionHandler :: ERROR_OUTPUT_NORMAL (deprecated + warnings)
         *     ErrorExceptionHandler :: ERROR_OUTPUT_ALL (deprecated, warnings + notices)
         */

        'ErrorHandling' => Nuwani \ ErrorExceptionHandler :: ERROR_OUTPUT_ALL,


        /**
         * Configuration: SleepTimer
         *
         * The sleeptimer indicates the number of microseconds the bot should
         * pause after every tick. While high values make the bot semi-slow in
         * replying to messages, the CPU usage will be really low. With low values,
         * the bot will respond more quickly, but CPU usage will increase
         * as well. Adviced values are:
         *
         *    High performance:    20000
         *    Normal performance:  40000
         *    Al-Gore modus:       100000
         *
         * Using values lower than 5000 or higher than 1000000 is highly
         * discouraged, as the CPU usage would either be really high or the
         * bot would become really inaccurate (think about timers).
         */

        'SleepTimer'    => 40000,
);

?>
