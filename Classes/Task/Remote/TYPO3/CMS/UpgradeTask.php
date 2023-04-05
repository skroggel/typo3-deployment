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

        $command = $this->getCliArgumentsForDatabaseDump($node, $application, $deployment, $options);
        if ($command['cliArguments']) {
            $this->executeCliCommand(
                $command['cliArguments'],
                $node,
                $application,
                $deployment,
                $options,
                $command['preCommand'],
                $command['postCommand']
            );
        }


        $command = $this->getCliArgumentsForDatabaseUpdateBefore($node, $application, $deployment, $options);
        if ($command['cliArguments']) {
            $this->executeCliCommand(
                $command['cliArguments'],
                $node,
                $application,
                $deployment,
                $options,
                $command['preCommand'],
                $command['postCommand']
            );
        }

        $command = $this->getCliArgumentsForCompareDatabase($node, $application, $deployment, $options);
        if ($command['cliArguments']) {
            $this->executeCliCommand(
                $command['cliArguments'],
                $node,
                $application,
                $deployment,
                $options,
                $command['preCommand'],
                $command['postCommand']
            );
        }

        $allWizards = $this->getCombinedWizardListWithArguments($options);
        foreach ($allWizards as $wizard => $wizardArguments) {
            $command = $this->getCliArgumentsForUpgradeWizard($node, $application, $deployment, $wizard, $wizardArguments, $options);
            if ($command['cliArguments']) {
                $this->executeCliCommand(
                    $command['cliArguments'],
                    $node,
                    $application,
                    $deployment,
                    $options,
                    $command['preCommand'],
                    $command['postCommand']
                );
            }
        }

        $command = $this->getCliArgumentsForCompareDatabase($node, $application, $deployment, $options);
        if ($command['cliArguments']) {
            $this->executeCliCommand(
                $command['cliArguments'],
                $node,
                $application,
                $deployment,
                $options,
                $command['preCommand'],
                $command['postCommand']
            );
        }

        $command = $this->getCliArgumentsForDatabaseUpdateAfter($node, $application, $deployment, $options);
        if ($command['cliArguments']) {
            $this->executeCliCommand(
                $command['cliArguments'],
                $node,
                $application,
                $deployment,
                $options,
                $command['preCommand'],
                $command['postCommand']
            );
        }
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForDatabaseUpdateBefore(
        Node $node,
        CMS $application,
        Deployment $deployment,
        array $options = []
    ): array {

        if (
            ($options['queryFileBeforeUpgrade'])
            && (file_exists($options['queryFileBeforeUpgrade']))
            && ($content = file_get_contents($options['queryFileBeforeUpgrade']))
        ) {
            return [
                'preCommand' => 'echo ' . escapeshellarg($content).  ' | ',
                'cliArguments' => [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:import'],
                'postCommand' => ''
            ];
        }

        return [
            'preCommand' => '',
            'cliArguments' => [],
            'postCommand' => ''
        ];
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForDatabaseUpdateAfter(
        Node $node,
        CMS $application,
        Deployment $deployment,
        array $options = []
    ): array {

        if (
            ($options['queryFileAfterUpgrade'])
            && (file_exists($options['queryFileAfterUpgrade']))
            && ($content = file_get_contents($options['queryFileAfterUpgrade']))
        ) {
            return [
                'preCommand' => 'echo ' . escapeshellarg($content).  ' | ',
                'cliArguments' => [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:import'],
                'postCommand' => ''
            ];
        }

        return [
            'preCommand' => '',
            'cliArguments' => [],
            'postCommand' => ''
        ];
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForCompareDatabase(
        Node $node,
        CMS $application,
        Deployment $deployment,
        array $options = []
    ): array {

        $databaseCompareMode = $options['databaseCompareMode'] ?? '*.add,*.change';
        return [
            'preCommand' => '',
            'cliArguments' => [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:updateschema', $databaseCompareMode],
            'postCommand' => ''
        ];
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param array $options
     * @return array
     */
    protected function getCliArgumentsForDatabaseDump(
        Node $node,
        CMS $application,
        Deployment $deployment,
        array $options = []
    ): array {

        if (
            (
                ($options['queryFileBeforeUpgrade'])
                && (file_exists($options['queryFileBeforeUpgrade']))
                && ($content = file_get_contents($options['queryFileBeforeUpgrade']))
            ) || (
                ($options['queryFileAfterUpgrade'])
                && (file_exists($options['queryFileAfterUpgrade']))
                && ($content = file_get_contents($options['queryFileAfterUpgrade']))
             )
        ) {
            return [
                'preCommand' => '',
                'cliArguments' => [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'database:export'],
                'postCommand' => ' > ' . escapeshellarg($deployment->getApplicationReleasePath($application) . '/' . time() . '-dump.sql')
            ];
        }

        return [
            'preCommand' => '',
            'cliArguments' => [],
            'postCommand' => ''
        ];
    }


    /**
     * @param Node $node
     * @param CMS $application
     * @param Deployment $deployment
     * @param string $wizard
     * @return array
     */
    protected function getCliArgumentsForUpgradeWizard(
        Node $node,
        CMS $application,
        Deployment $deployment,
        string $wizard,
        string $wizardArguments,
        array $options = []
    ): array {

        return [
            'preCommand' => '',
            'cliArguments' => [$this->getConsoleScriptFileName($node, $application, $deployment, $options), 'upgrade:wizard', $wizard, $wizardArguments],
            'postCommand' => ''
        ];
    }


    /**
     * @param array $options
     * @return array
     */
    protected function getCombinedWizardListWithArguments (array $options = []): array
    {
        $allWizards = [
            'formFileExtension','extensionManagerTables','wizardDoneToRegistry','startModuleUpdate',
            'frontendUserImageUpdateWizard','databaseRowsUpdateWizard','commandLineBackendUserRemovalUpdate',
            'fillTranslationSourceField','sectionFrameToFrameClassUpdate','splitMenusUpdate',
            'bulletContentElementUpdate','uploadContentElementUpdate','migrateFscStaticTemplateUpdate',
            'fileReferenceUpdate','migrateFeSessionDataUpdate','compatibility7Extension',
            'formLegacyExtractionUpdate','rtehtmlareaExtension','sysLanguageSorting','typo3DbLegacyExtension',
            'funcExtension','pagesUrltypeField','separateSysHistoryFromLog','rdctExtension',
            'cshmanualBackendUsers','pagesLanguageOverlay','pagesLanguageOverlayBeGroupsAccessRights',
            'backendLayoutIcons','redirects','adminpanelExtension','pagesSlugs','argon2iPasswordHashes',
            'backendUsersConfiguration','svgFilesSanitization','masiMigrateRealUrlExclude','seoTitleUpdate',
            'canonicalFieldUpdate'
        ];

        if (! $options['excludeWizards']) {
            $options['excludeWizards'] = 'rtehtmlareaExtension, funcExtension,typo3DbLegacyExtension,rdctExtension,' .
                'compatibility7Extension,redirects,adminpanelExtension,argon2iPasswordHashes';
        }
        $excludeWizards = explode(',', preg_replace("/\s/", '',$options['excludeWizards']));

        $wizardList = [];
        foreach ($allWizards as $wizard) {

            $params = '-a ' . $wizard . '[install]=1';
            if (in_array($wizard, $excludeWizards )) {
                $params = '-a ' . $wizard . '[install]=0';
            }

            $wizardList[$wizard] = $params;
        }

        return $wizardList;
    }


    /**
     * Execute this task
     *
     * @param array $cliArguments
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param CMS $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return bool|mixed
     */
    protected function executeCliCommand(
        array $cliArguments,
        Node $node,
        CMS $application,
        Deployment $deployment,
        array $options = [],
        string $preCommand = '',
        string $postCommand = ''
    ) {

        $this->determineWorkingDirectoryAndTargetNode($node, $application, $deployment, $options);
        $phpBinaryPathAndFilename = $options['phpBinaryPathAndFilename'] ?? 'php';
        $commandPrefix = '';
        if (isset($options['context'])) {
            $commandPrefix = 'TYPO3_CONTEXT=' . escapeshellarg($options['context']) . ' ';
        }
        $commandPrefix .= $phpBinaryPathAndFilename . ' ';

        $this->determineWorkingDirectoryAndTargetNode($node, $application, $deployment, $options);

        $commands = ['cd ' . escapeshellarg($this->workingDirectory)];
        $commands[] = ($preCommand ? $preCommand . ' ': '')
            . $commandPrefix . implode(' ', array_map('escapeshellarg', $cliArguments))
            . ($postCommand ? $postCommand . ' ': '');

        return $this->shell->executeOrSimulate($commands, $this->targetNode, $deployment);
    }

}
