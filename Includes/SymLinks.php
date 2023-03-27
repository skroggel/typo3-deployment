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
    './web/uploads' => '../../../surf/shared/Data/uploads',
    './web/fileadmin' => '../../../surf/shared/Data/fileadmin',
    './web/typo3temp/logs' => '../../../../shared/Data/logs', // deprecated path
    './web/typo3temp/var/logs' => '../../../../../shared/Data/logs', // deprecated path
    './var/logs' => '../../../shared/Data/logs',
    './web/typo3temp/assets' => '../../../../shared/Data/assets',
    './web/typo3conf/LocalConfiguration.php' => '../../../../shared/LocalConfiguration.php'
];
