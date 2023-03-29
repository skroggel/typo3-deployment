<?php
namespace Madj2k\Surf\Task\Local\File;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\Surf\Task\LocalShellTask;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Class CopyHtaccessTask
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_Surf
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CopyHtaccessTask extends LocalShellTask
{

    /**
     * Execute this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     * @returns void
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $webDir = escapeshellarg(($this->getOption('webDirectory')? trim($options['webDirectory'], '\\/') .'/' : ''));
        $fileExtension = preg_replace('/[^a-zA-Z0-9]/', '', $this->getOption('fileExtension'));
        $options['command'] =  'cd {workspacePath}' .
            ' && if [ -f "./' . $webDir . '_.htaccess.' . $fileExtension . '" ]; then' .
                ' cp ./' . $webDir . '_.htaccess.' . $fileExtension . ' ./' . $webDir . '.htaccess;' .
            ' fi' .
            ' && if [ -f "./' . $webDir . '_.htpasswd.' . $fileExtension . '" ]; then' .
                ' cp ./' . $webDir . '_.htpasswd.' . $fileExtension . ' ./' . $webDir . '.htpasswd;' .
            ' fi' .
            ' && echo "Copied .htaccess in {workspacePath}/' . $webDir . '.";';

        parent::execute($node, $application, $deployment, $options);
    }

}
