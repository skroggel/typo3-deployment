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

use TYPO3\Surf\Task\TYPO3\CMS\AbstractCliTask;
use TYPO3\Surf\Application\TYPO3\CMS;
use TYPO3\Surf\DeprecationMessageFactory;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Class CreatePackageStatesTask
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_Surf
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CreatePackageStatesTask extends AbstractCliTask
{
    /**
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
        if (!$this->packageStatesFileExists($node, $application, $deployment, $options)) {
            try {
                $scriptFileName = $this->getConsoleScriptFileName($node, $application, $deployment, $options);
            } catch (InvalidConfigurationException $e) {
                throw new InvalidConfigurationException('No package states file found in the repository and no typo3_console package found to generate it. We cannot proceed.', 1420210956, $e);
            }
            $commandArguments = [$scriptFileName, 'install:generatepackagestates'];
            if (!empty($options['removeInactivePackages'])) {
                $commandArguments[] = '--remove-inactive-packages';
            }
            $this->executeCliCommand($commandArguments, $node, $application, $deployment, $options);
        }
    }


    /**
     * Simulate this task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @return void
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $this->execute($node, $application, $deployment, $options);
    }


    /**
     * Checks if the package states file exists
     *
     * If no manifest exists, a log message is recorded.
     *
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return bool
     */
    protected function packageStatesFileExists(Node $node, CMS $application, Deployment $deployment, array $options = []): bool
    {
        $webDirectory = isset($options['webDirectory']) ? trim($options['webDirectory'], '\\/') : '';
        return $this->fileExists($webDirectory . '/typo3conf/PackageStates.php', $node, $application, $deployment, $options);
    }
}
