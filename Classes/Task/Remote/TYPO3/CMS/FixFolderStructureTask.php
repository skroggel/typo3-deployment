<?php
namespace Madj2k\Surf\Task\Remote\TYPO3\CMS;

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

use TYPO3\Surf\Application\TYPO3\CMS;
use TYPO3\Surf\Task\TYPO3\CMS\AbstractCliTask;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Class FixFolderStructureTask
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_Surf
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FixFolderStructureTask extends AbstractCliTask
{

    /**
     * Execute this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        if ($this->getAvailableCliPackage($node, $application, $deployment, $options) !== 'typo3_console') {
            $this->logger->warning('Extension "typo3_console" was not found! Make sure one is available in your project, or remove this task (' . __CLASS__ . ') in your deployment configuration!');
            return;
        }

        $cliArguments = $this->getSuitableCliArguments($node, $application, $deployment, $options);
        $this->executeCliCommand(
            $cliArguments,
            $node,
            $application,
            $deployment,
            $options
        );
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getSuitableCliArguments(Node $node, CMS $application, Deployment $deployment, array $options = []): array
    {
        return [$this->getTypo3ConsoleScriptFileName($node, $application, $deployment, $options), 'install:fixfolderstructure'];
    }

}
