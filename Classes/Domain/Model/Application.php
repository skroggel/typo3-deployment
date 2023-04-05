<?php
namespace Madj2k\Surf\Domain\Model;

/**
 * Class Application
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_Surf
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later */

use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Workflow;

class Application extends \TYPO3\Surf\Application\TYPO3\CMS
{

   /**
    * Constructor
    * @param string $name
    * @return void
    */
    public function __construct(string $name = 'TYPO3 CMS')
    {

        parent::__construct($name);
        $this->options = array_merge($this->options, array(
            'context' => 'Production/Staging',
            'projectName' => '',
            'repositoryUrl' => '',
            'packageMethod' => 'git',
            'transferMethod' => 'rsync',
            'updateMethod' => null,
            'keepReleases' => 3,
            'composerCommandPath' => 'composer',
            'phpBinaryPathAndFilename' => '/usr/bin/php7.4',
            'branch' => '',
            'lockDeployment' => true,
            'applicationRootDirectory' => 'src',
            'deploymentPath' => '',
            'scriptFileName' => 'vendor/bin/typo3cms',
            'webDirectory' => 'web',
            'adminMail' => 'deployment@steffenkroggel.de',
            'doUpgrade' => false,
            'excludeWizards' => '',
            'queryFileBeforeUpgrade' => '',
            'queryFileAfterUpgrade' => '',
        ));
    }



    /**
     * Init application
     *
     * @param $options
     * @throws \Madj2k\Surf\Exception
     */
    public function initApplication($options)
    {

        // set options based on allowed options
        $mergedOptions = array_merge($this->options, $options);
        foreach (array_keys($this->options) as $key) {

            if (
                (! isset($mergedOptions[$key]))
                && (! is_null($mergedOptions[$key]))
            ){
                throw new \Madj2k\Surf\Exception(sprintf('Param "%s" has not been set.', $key));
            }

            if ($key == 'deploymentPath') {
                $this->setDeploymentPath($mergedOptions[$key]);

            } else {

                $this->setOption($key, $mergedOptions[$key]);

                if ($key == 'branch') {
                    $this->setOption('typo3.surf:gitCheckout[branch]', $mergedOptions[$key]);
                }

                // override name of application
                if ($key == 'projectName') {
                    $this->setName(md5($mergedOptions[$key]));
                }
            }
        }

        // set rsync flags
        if ($this->getOption('transferMethod') == 'rsync') {
            $rsyncFlags = require_once (__DIR__ . '/../../../Includes/RsyncFlags.php');
            $this->setOption('rsyncFlags', implode(' ', $rsyncFlags));
        }

        // set file extension based on branch
        if ($context = strtolower($this->getOption('context'))) {

            $this->setOption('fileExtension', 'dev');
            if (strpos($context,'staging') !== false) {
                $this->setOption('fileExtension', 'stage');
            } else if (strpos($context,'production') !== false) {
                $this->setOption('fileExtension', 'prod');
            }
        }

        // set symlinks
        $symLinks = require_once (__DIR__ . '/../../../Includes/SymLinks.php');
        $this->setSymlinks($symLinks);
    }


    /**
     * Register all relevant task
     *
     * @param Workflow $workflow
     * @param Deployment $deployment
     */
    public function registerTasks(Workflow $workflow, Deployment $deployment)
    {

        parent::registerTasks($workflow, $deployment);

        // remove tasks we don't need or we want to handle ourselves!
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CopyConfigurationTask'); // is deprecated
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CreatePackageStatesTask'); // is deprecated
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\SetUpExtensionsTask'); // not needed, throws exceptions
        $workflow->removeTask('TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask'); // we use an own task for that
        $workflow->removeTask('TYPO3\\Surf\\Task\\Package\\GitTask'); // we add this later on again

        // -----------------------------------------------
        // Step 1: initialize - This is normally used only for an initial deployment to an instance.
        // At this stage you may prefill certain directories for example.

        // -----------------------------------------------
        // Step 2: lock

        // -----------------------------------------------
        // Step 3: package - This stage is where you normally package all files and assets, which will be transferred to the next stage.
        $workflow->addTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'package');
        $workflow->addTask('Madj2k\\Surf\\Task\\Local\\File\\CopyEnvTask', 'package');
        $workflow->addTask('Madj2k\\Surf\\Task\\Local\\File\\CopyServerConfigurationTask', 'package');
        $workflow->addTask('Madj2k\\Surf\\Task\\Local\\File\\CopyAdditionalConfigurationTask', 'package');
        $workflow->addTask('Madj2k\\Surf\\Task\\Local\\File\\FixPermissionsTask', 'package');
        $workflow->addTask('Madj2k\\Surf\\Task\\Local\\Git\\SetFileModeIgnoreTask', 'package');
        $workflow->addTask('Madj2k\\Surf\\Task\\Local\\Composer\\InstallTask', 'package');

        // -----------------------------------------------
        // Step 4: transfer - Here all tasks are located which serve to transfer the assets from your local computer to the node, where the application runs.
        $workflow->beforeTask('TYPO3\\Surf\\Task\\Generic\\CreateSymlinksTask', 'Madj2k\\Surf\\Task\\Remote\\File\\CreateVarFoldersTask');
        $workflow->afterTask('TYPO3\\Surf\\Task\\Generic\\CreateSymlinksTask', 'Madj2k\\Surf\\Task\\Remote\\TYPO3\\CMS\\CreatePackageStatesTask');

        // -----------------------------------------------
        // Step 5: update - If necessary, the transferred assets can be updated at this stage on the foreign instance.
        // Be careful and do not delete old tables or columns, because the old code, relying on these, is still live.
        $workflow->addTask('Madj2k\\Surf\\Task\\Remote\\TYPO3\\CMS\\LockForEditorsTask', 'update');
        $workflow->addTask('Madj2k\\Surf\\Task\\Remote\\TYPO3\\CMS\\UpgradeTask', 'update');

        // -----------------------------------------------
        // Step 6: migration - Here you can define tasks to do some database updates / migrations.
        // Be careful and do not delete old tables or columns, because the old code, relying on these, is still live.
        $workflow->addTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CompareDatabaseTask', 'migrate');

        // -----------------------------------------------
        // Step 7: finalize - This stage is meant for tasks, that should be done short before going live, like cache warm ups and so on.
        $workflow->addTask( 'Madj2k\\Surf\\Task\\Remote\\File\\FixPermissionsTask', 'finalize');
        $workflow->addTask('Madj2k\\Surf\\Task\\Remote\\TYPO3\\CMS\\FixFolderStructureTask', 'finalize');
        if (strtolower($this->getOption('context')) != 'production') {
            $workflow->afterStage('finalize', 'Madj2k\\Surf\\Task\\Remote\\File\\CopyDummyFilesTask');
        }

        // -----------------------------------------------
        // Step 8: test - In the test stage you can make tests, to check if everything is fine before switching the releases.

        // -----------------------------------------------
        // Step 9: switch - This is the crucial stage. Here the old live instance is switched with the new prepared instance. Normally the new instance is symlinked.
        // $workflow->beforeStage('switch', 'Madj2k\\Surf\\Task\\Remote\\ClearOpcCache');
        // $workflow->afterTask('Madj2k\\Surf\\Task\\Remote\\ClearOpcCache', 'TYPO3\\Surf\\Task\\TYPO3\\CMS\\FlushCachesTask');

        // -----------------------------------------------
        // Step 10: cleanup - At this stage you would cleanup old releases or remove other unused stuff.
        // $workflow->beforeStage('cleanup', 'Madj2k\\Surf\\Task\\Remote\\ClearOpcCache');

        // -----------------------------------------------
        // Step 11: unlock
        $workflow->addTask('Madj2k\\Surf\\Task\\Remote\\TYPO3\\CMS\\UnlockForEditorsTask', 'unlock');
        $workflow->afterStage('unlock', 'Madj2k\\Surf\\Task\\Remote\\NotifyTask');

    }

}
