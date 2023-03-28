<?php
namespace Madj2k\Surf\Task\Local\Composer;
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
 * Class RemoveLockTask
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_Surf
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RemoveLockTask extends LocalShellTask
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

        // own task because we need --prefer-dist
        // we do NOT set no-scripts, because when using no-scripts the TYPO3 console won't work
        $options['command'] =  'cd {workspacePath}' . 
            ' && rm ./composer.lock' . 
            ' && eecho "Removed composer.lock in {workspacePath}"';

        parent::execute($node, $application, $deployment, $options);
    }

}
