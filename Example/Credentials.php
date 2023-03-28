<?php
/**
 * Credentials
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @version 1.0.3
 */
return [
    'projectName' => 'Example',
    'deploymentPath' => '/var/www/example.com/surf',
    'phpBinaryPathAndFilename' => '/usr/bin/php7.4',
    'adminMail' => 'deployment@example.com',

    'hostname' => '',
    'username' => '',
    'password' => '',
    'port' => '',

    'repositoryUrl' => 'https://github.com/Test/Example.git',
    'branch' => 'staging',
    
    'doUpgrade' => false,
    'queryFileBeforeUpgrade' => '',
    'queryFileAfterUpgrade' => ''
];
