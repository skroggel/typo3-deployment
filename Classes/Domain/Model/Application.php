<?php
namespace Madj2k\SurfDeployment\Domain\Model;

/**
 * Class Application
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_T3Deployment
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
            'projectName' => '',
            'repositoryUrl' => '',
            'packageMethod' => 'git',
            'transferMethod' => 'rsync',
            'updateMethod' => null,
            'keepReleases' => 3,
            'composerCommandPath' => 'composer',
            'phpBinaryPathAndFilename' => '/usr/bin/php7.4',
            'branch' => '',
            'applicationRootDirectory' => 'src',
            'deploymentPath' => '',
            'context' => 'Production',
            'scriptFileName' => 'vendor/bin/typo3cms',
            'webDirectory' => 'web',
            'adminMail' => 'deployment@steffenkroggel.de',
        ));
    }



    /**
     * Init application
     *
     * @param $options
     * @throws \Madj2k\SurfDeployment\Exception
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
                throw new \Madj2k\SurfDeployment\Exception(sprintf('Param "%s" has not been set.', $key));
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
        if ($branch = $this->getOption('branch')) {

            $this->setOption('fileExtension', 'dev');
            if ($branch == 'staging') {
                $this->setOption('fileExtension', 'stage');
            } else if ($branch == 'production') {
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
        $this->defineTasks($workflow);

        // remove tasks we don't need or we want to handle ourselves!
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CopyConfigurationTask'); // is deprecated
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\SetUpExtensionsTask'); // not needed, throws exceptions
        $workflow->removeTask('TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask'); // we use an own task for that
        $workflow->removeTask('TYPO3\\Surf\\Task\\Package\\GitTask'); // we add this later on again

        // -----------------------------------------------
        // Step 1: initialize - This is normally used only for an initial deployment to an instance. At this stage you may prefill certain directories for example.

        // -----------------------------------------------
        // Step 2: package - This stage is where you normally package all files and assets, which will be transferred to the next stage.
        $workflow->addTask('Madj2k\\Surf\\Task\\Local\\ComposerInstall' . ucfirst($this->getOption('branch')), 'package');
        $workflow->beforeTask('Madj2k\\Surf\\Task\\Local\\ComposerInstall' . ucfirst($this->getOption('branch')), 'TYPO3\\Surf\\Task\\Package\\GitTask');

        $workflow->afterTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'Madj2k\\Surf\\Task\\Local\\CopyEnv');
        $workflow->afterTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'Madj2k\\Surf\\Task\\Local\\CopyHtaccess');
        $workflow->afterTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'Madj2k\\Surf\\Task\\Local\\CopyAdditionalConfiguration');
        $workflow->afterTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'Madj2k\\Surf\\Task\\Local\\FixRights');

        $workflow->afterTask('Madj2k\\Surf\\Task\\Local\\FixRights', 'Madj2k\\Surf\\Task\\Local\\SetGitFileModeIgnore');

        // -----------------------------------------------
        // Step 3: transfer - Here all tasks are located which serve to transfer the assets from your local computer to the node, where the application runs.
        $workflow->beforeTask('TYPO3\\Surf\\Task\\Generic\\CreateSymlinksTask', 'Madj2k\\Surf\\Task\\Remote\\CreateTypo3Temp');

        // -----------------------------------------------
        // Step 4: update - If necessary, the transferred assets can be updated at this stage on the foreign instance.

        // -----------------------------------------------
        // Step 5: migration - Here you can define tasks to do some database updates / migrations. Be careful and do not delete old tables or columns, because the old code, relying on these, is still live.
        $workflow->addTask('Madj2k\\Surf\\Task\\Remote\\TYPO3\\UpdateSchema', 'migrate');

        // -----------------------------------------------
        // Step 6: finalize - This stage is meant for tasks, that should be done short before going live, like cache warm ups and so on.
        $workflow->beforeStage('finalize', 'Madj2k\\Surf\\Task\\Remote\\FixRights');
        $workflow->afterStage('finalize', 'Madj2k\\Surf\\Task\\Remote\\TYPO3\\FixFolderStructure');
        $workflow->afterStage('finalize', 'Madj2k\\Surf\\Task\\Remote\\Madj2kSearch\\FixRights');
        if ($this->getOption('branch') != 'production') {
            $workflow->afterStage('finalize', 'Madj2k\\Surf\\Task\\Remote\\CopyDummyFiles');
        }

        // -----------------------------------------------
        // Step 7: test - In the test stage you can make tests, to check if everything is fine before switching the releases.

        // -----------------------------------------------
        // Step 8: switch - This is the crucial stage. Here the old live instance is switched with the new prepared instance. Normally the new instance is symlinked.
        $workflow->beforeStage('switch', 'Madj2k\\Surf\\Task\\Remote\\ClearOpcCache');
        $workflow->afterTask('Madj2k\\Surf\\Task\\Remote\\ClearOpcCache', 'TYPO3\\Surf\\Task\\TYPO3\\CMS\\FlushCachesTask');

        // -----------------------------------------------
        // Step 9: cleanup - At this stage you would cleanup old releases or remove other unused stuff.
        $workflow->beforeStage('cleanup', 'Madj2k\\Surf\\Task\\Remote\\ClearOpcCache');
        $workflow->afterStage('cleanup', 'Madj2k\\Surf\\Task\\Remote\\EmailNotification');


        // @toDo: Make it work :)
        /*if ($varnishAdmPath) {
            $workflow->addTask('TYPO3\\Surf\\Task\\VarnishBanTask', 'cleanup');
        }*/

    }


    /**
     * Define the individual tasks
     *
     * All these tasks require the typo3_console. Please install it with your projects composer json.
     *
     * @param Workflow $workflow
     * @return void
     */
    private function defineTasks(Workflow $workflow)
    {

        //---------------------------------------
        // define task executed locally
        //---------------------------------------
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\FixRights',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && chmod -R 770 ./ && echo "Fixed rights in {workspacePath}"']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\SetGitFileModeIgnore',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && ./scripts/git-filemode-recursive.sh && echo "Set \'git config core.filemode false\' on all repositories in {workspacePath}"']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\CopyEnv',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && if [ -f "_.env.' . $this->getOption('fileExtension') . '" ]; then cp _.env.' . $this->getOption('fileExtension') . ' .env; fi']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\CopyAdditionalConfiguration',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && if [ -f "./web/typo3conf/AdditionalConfiguration.' . $this->getOption('fileExtension') . '.php" ]; then cp ./web/typo3conf/AdditionalConfiguration.' . $this->getOption('fileExtension') . '.php ./web/typo3conf/AdditionalConfiguration.php; fi && echo "Copied AdditionalConfiguration.php in {workspacePath}."']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\CopyHtaccess',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && if [ -f "./web/_.htaccess.' . $this->getOption('fileExtension') . '" ]; then cp ./web/_.htaccess.' . $this->getOption('fileExtension') . ' ./web/.htaccess; fi && if [ -f "./web/_.htpasswd.' . $this->getOption('fileExtension') . '" ]; then cp ./web/_.htpasswd.' . $this->getOption('fileExtension') . ' ./web/.htpasswd; fi && echo "Copied .htaccess in {workspacePath}."']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\RemoveComposerLock',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && rm ./composer.lock && echo "Removed composer.lock in {workspacePath}"']
        );
        // own task because we need --prefer-dist
        // we do NOT set no-scripts, because when using no-scripts the TYPO3 console won't work
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\ComposerInstallProduction',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && export TYPO3_DEPLOYMENT_RUN="1" && composer install --no-ansi --no-interaction --no-dev --no-progress --classmap-authoritative --prefer-dist 2>&1 && export TYPO3_DEPLOYMENT_RUN="0"']
        );

        // without --no-dev here. This way we can deploy development stuff to the staging environment without having to take care for production environment.
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Local\\ComposerInstallStaging',
            \TYPO3\Surf\Task\LocalShellTask::class,
            ['command' => 'cd {workspacePath} && export TYPO3_DEPLOYMENT_RUN="1" && composer install --no-ansi --no-interaction --no-progress --classmap-authoritative --prefer-dist 2>&1 && export TYPO3_DEPLOYMENT_RUN="0"']
        );

        //---------------------------------------
        // define task executed remotely
        //---------------------------------------
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\CopyDummyFiles',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && if [ -d "./dummy" ] && [ -d "./web/fileadmin/" ]; then if [ ! -d "./web/fileadmin/media" ]; then mkdir ./web/fileadmin/media; fi && cp ./dummy/* ./web/fileadmin/media/ && echo "Copied dummy files in {releasePath}."; fi']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\CreateTypo3Temp',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && if [ ! -d "./web/typo3temp/" ]; then mkdir ./web/typo3temp; fi && if [ ! -d "./web/typo3temp/var" ]; then mkdir ./web/typo3temp/var; fi']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\FixRights',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && find ./web -type f -exec chmod 640 {} \; && find ./web -type d -exec chmod 750 {} \; && echo "Fixed rights in {releasePath}"']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\ClearOpcCache',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && ' . $this->getOption('phpBinaryPathAndFilename') . ' ./clear-opc-cache.php']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\TYPO3\\FixFolderStructure',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && ./vendor/bin/typo3cms install:fixfolderstructure']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\TYPO3\\UpdateSchema',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && if [ -f "./web/typo3conf/LocalConfiguration.php" ]; then ./vendor/bin/typo3cms database:updateschema "*.add,*.change"; fi']
        );
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\EmailNotification',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && if [ -f "./web/changelog" ]; then mail -s "A new release is online now! (branch \"' . $this->getOption('branch') . '\")" ' . $this->getOption('adminMail') . ' < ./web/changelog; fi']
        );

        // Extension Specific Tasks
        $workflow->defineTask(
            'Madj2k\\Surf\\Task\\Remote\\Madj2kSearch\\FixRights',
            \TYPO3\Surf\Task\ShellTask::class,
            ['command' => 'cd {releasePath} && if [ -f "./web/typo3conf/ext/Madj2k_search" ]; then chmod 755 ./web/typo3conf/ext/Madj2k_search/Classes/Libs/TreeTagger/bin/* && chmod 755 ./web/typo3conf/ext/Madj2k_search/Classes/Libs/TreeTagger/cmd/* && echo "Fixed rights for Madj2k_search"; fi']
        );


        /*
        // Set options for varnish ban
        if ($varnishAdmPath) {
            $workflow->setTaskOptions('TYPO3\\Surf\\Task\\VarnishBanTask',
                [
                    'varnishadm' => $varnishAdmPath,
                    'secretFile' => $varnishSecret,
                    'banUrl' => $varnishBanUrl
                ]
            );
        }*/

    }

}
