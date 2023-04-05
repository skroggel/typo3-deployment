<?php
namespace Madj2k\Surf\Task\Remote\File;
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

use TYPO3\Surf\Task\ShellTask;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Class CopyDummyFilesTask
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_Surf
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CopyDummyFilesTask extends ShellTask
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
        $webDir = ($options['webDirectory']? trim($options['webDirectory'], '\\/') .'/' : '');
        $options['command'] = 'cd {releasePath}' .
            ' && if [ -d "./dummy" ] && [ -d ' . escapeshellarg('./' . $webDir . 'fileadmin/') . ' ]; then' .
                ' if [ ! -d ' . escapeshellarg('./' . $webDir . 'fileadmin/media') . ' ]; then' .
                    ' mkdir ' . escapeshellarg('./' . $webDir . 'fileadmin/media') . ';' .
                ' fi' .
                ' && cp ./dummy/* ' . escapeshellarg('./' . $webDir . 'fileadmin/media/') .
                ' && echo "Copied dummy files in {releasePath}/' . $webDir . '.";' .
            ' fi';

        parent::execute($node, $application, $deployment, $options);
    }

}
