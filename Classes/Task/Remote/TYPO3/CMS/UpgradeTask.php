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
 * Class UpgradeTask
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_Surf
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class UpgradeTask extends AbstractCliTask
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
        $this->ensureApplicationIsTypo3Cms($application);

        if ($this->getAvailableCliPackage($node, $application, $deployment, $options) !== 'typo3_console') {
            $deployment->getLogger()->warning('Extension "typo3_console" was not found! Make sure one is available in your project, or remove this task (' . __CLASS__ . ') in your deployment configuration!');
            return;
        }

        if (! $options['doUpgrade']) {
            $deployment->getLogger()->notice('Upgrade-option is not set. Set "doUpgrade" to true to do an upgrade.');
            return;
        }

        $cliArguments = $this->getCliArgumentsForDatabaseDump($node, $application, $deployment, $options);
        $this->executeCliCommand(
            $cliArguments,
            $node,
            $application,
            $deployment,
            $options
        );        

        $cliArguments = $this->getCliArgumentsForDatabaseUpdateBefore($node, $application, $deployment, $options);
        $this->executeCliCommand(
            $cliArguments,
            $node,
            $application,
            $deployment,
            $options
        );

        $cliArguments = $this->getCliArgumentsForCompareDatabase($node, $application, $deployment, $options);
        $this->executeCliCommand(
            $cliArguments,
            $node,
            $application,
            $deployment,
            $options
        );

        $cliArguments = $this->getCliArgumentsForUpgrade($node, $application, $deployment, $options);
        $this->executeCliCommand(
            $cliArguments,
            $node,
            $application,
            $deployment,
            $options
        );

        $cliArguments = $this->getCliArgumentsForDatabaseUpdateAfter($node, $application, $deployment, $options);
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
    protected function getCliArgumentsForDatabaseUpdateBefore(Node $node, CMS $application, Deployment $deployment, array $options = [])
    {
        if (
            ($options['queryFileBeforeUpgrade'])
            && (file_exists($options['queryFileBeforeUpgrade']))
            && ($content = file_get_contents($options['queryFileBeforeUpgrade']))
        ) {
            return ['echo', escapeshellarg($content), '|', $this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:import'];
        }
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForDatabaseUpdateAfter(Node $node, CMS $application, Deployment $deployment, array $options = [])
    {
        if (
            ($options['queryFileAfterUpgrade'])
            && (file_exists($options['queryFileAfterUpgrade']))
            && ($content = file_get_contents($options['queryFileAfterUpgrade']))
        ) {
            return ['echo', escapeshellarg($content), '|', $this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:import'];
        }
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForCompareDatabase(Node $node, CMS $application, Deployment $deployment, array $options = [])
    {
        $databaseCompareMode = $options['databaseCompareMode'] ?? '*.add,*.change';
        return [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:updateschema', $databaseCompareMode];
    }

    
    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForDatabaseDump(Node $node, CMS $application, Deployment $deployment, array $options = [])
    {
        return [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:export', '>', $deployment->getApplicationReleasePath($application) .'/' . time() . '-dump.sql'];
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForUpgrade(Node $node, CMS $application, Deployment $deployment, array $options = [])
    {
        $additionalParamsUpgrade = $options['additionalParamsUpgrade'] ?? '--deny typo3DbLegacyExtension --deny funcExtension --deny rdctExtension --deny redirects --deny adminpanelExtension --deny argon2iPasswordHashes';
        return [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'upgrade:run all', '--no-interaction', '--confirm all', $additionalParamsUpgrade];
    }

}
