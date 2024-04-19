<?php
/**
 * SymLinks
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_T3Deployment
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
return [
    './public/fileadmin' => '###DEPLOYMENT_PATH###/shared/Data/fileadmin',
    './var/log' => '###DEPLOYMENT_PATH###/shared/Data/var/logs',
    './var/labels' => '###DEPLOYMENT_PATH###/shared/Data/var/labels',
    './public/typo3temp/assets' => '###DEPLOYMENT_PATH###/shared/Data/typo3temp/assets',
    './public/typo3conf/LocalConfiguration.php' => '###DEPLOYMENT_PATH###/shared/LocalConfiguration.php',
    './uploads' => '###DEPLOYMENT_PATH###/shared/Data/uploads',
];
