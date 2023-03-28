<?php
use  Madj2k\TYPO3Deployment\Deployment;

/**
 * Deployment-Script
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_T3Deployment
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */

// load options
$options = require_once __DIR__ . '/Credentials.php';

// make deployment
$Madj2kDeployment = new Deployment($deployment, $options);
