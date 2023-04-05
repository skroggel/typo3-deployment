# Stages of deployment
When running a deployment the following stages and corresponding tasks are executed.

##  Stage 1: initialize
This is normally used only for an initial deployment to an instance.
At this stage you may prefill certain directories for example.
1. TYPO3\Surf\Task\Generic\CreateDirectoriesTask
2. TYPO3\Surf\Task\CreateDirectoriesTask
3. TYPO3\Surf\Task\DumpDatabaseTask (wenn initialDeployment == true)
4. TYPO3\Surf\Task\RsyncFoldersTask (wenn initialDeployment == true)

##  Stage 2: lock deployment
Lock deployment

BEFORE:
1. TYPO3\Surf\Task\UnlockDeploymentTask (wenn --force-run)

REGULAR:
1. TYPO3\Surf\Task\LockDeploymentTask

## Stage 3: package
This stage is where you normally package all files and assets, which will be transferred to the next stage.
1. TYPO3\Surf\Task\Package\GitTask
2. ~~TYPO3\Surf\DefinedTask\Composer\LocalInstallTask~~
3. Madj2k\\Surf\\Task\\Local\\File\\CopyEnvTask
4. Madj2k\\Surf\\Task\\Local\\File\\CopyHtaccessTask
5. Madj2k\\Surf\\Task\\Local\\File\\CopyAdditionalConfigurationTask
6. Madj2k\\Surf\\Task\\Local\\File\\FixPermissionsTask
7. Madj2k\\Surf\\Task\\Local\\Git\\SetFileModeIgnoreTask
8. Madj2k\Surf\Task\Local\Composer\InstallStagingTask OR Madj2k\Surf\Task\Local\Composer\InstallProductionTask

## Stage 4: transfer
Here all tasks are located which serve to transfer the assets from your local computer to the node, where the application runs.
1. TYPO3\Surf\Task\Transfer

AFTER:
1. Madj2k\Surf\Task\Remote\File\CreateVarFoldersTask
2. TYPO3\Surf\Task\Generic\CreateSymlinksTask
3. TYPO3\Surf\Task\TYPO3\CMS\CreatePackageStatesTask

## Stage 5: update
Update the application assets on the node

BEFORE:
1. Madj2k\Surf\Task\Remote\CMS\LockForEditorsTask

REGULAR:
1. Madj2k\Surf\Task\Remote\CMS\UpgradeTask

AFTER:
1. TYPO3\Surf\Task\TYPO3\CMS\SymlinkDataTask
2. ~~TYPO3\Surf\Task\TYPO3\CMS\CopyConfigurationTask~~

## Stage 6: migrate
Migrate (Doctrine, custom)
1. ~~TYPO3\Surf\Task\TYPO3\CMS\SetUpExtensionsTask~~
2. TYPO3\Surf\Task\TYPO3\CMS\CompareDatabaseTask

## Stage 7: finalize
Prepare final release (e.g. warmup)

REGULAR:
1. Madj2k\Surf\Task\Remote\File\FixPermissionsTask
2. Madj2k\Surf\Task\Remote\TYPO3\CMS\FixFolderStructureTask

AFTER:
1. Madj2k\\Surf\\Task\\Remote\\File\\CopyDummyFilesTask (only if NOT on production!)

## Stage 8: test
Smoke test

## Stage 9: Switch
Do symlink to current release. This is the crucial stage. Here the old live instance is switched with the new prepared instance. Normally the new instance is symlinked.
1. TYPO3\Surf\Task\SymlinkReleaseTask

AFTER:
1. TYPO3\Surf\Task\TYPO3\CMS\FlushCachesTask

## Stage 10: cleanup
Delete temporary files or previous releases
1. TYPO3\Surf\Task\CleanupReleasesTask

## Stage 11: unlock
Unlock deployment
1. TYPO3\Surf\Task\UnlockDeploymentTask
2. Madj2k\Surf\Task\Remote\TYPO3\CMS\UnlockForEditorsTask

AFTER:
1. Madj2k\Surf\Task\Remote\EmailNotification

# Folders and files for your deployment project
If you want to deploy your TYPO3 project with this extension, you should create a few folder structures and files to be able to use the full potential.

## Folder: .surf
Contains the configuration for the TYPO3 extension Surf for deployment and the corresponding Deployment-Scripts.

### File: .surf/Production.php
```
<?php
use Madj2k\Surf\Deployment;

/**
 * Deployment-Script
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @var \TYPO3\Surf\Domain\Model\Deployment $deployment
 */

// load options
$options = require_once __DIR__ . '/Credentials/Production.php';

// make deployment
$myDeployment = new Deployment($deployment, $options);
```

### File:  .surf/Staging.php

```
<?php
use Madj2k\Surf\Deployment;

/**
 * Deployment-Script
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @var \TYPO3\Surf\Domain\Model\Deployment $deployment
 */

// load options
$options = require_once __DIR__ . '/Credentials/Staging.php';

// make deployment
$myDeployment = new Deployment($deployment, $options);
```

### Folder: .surf/Credentials
Contains the access data for the respective environments.
**IMPORTANT: Do not put these files into your versioning!**

Example:
```
<?php
/**
 * Credentials
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @version 1.0.3
 */
return [
    'context' => 'Production/Staging',
    'projectName' => 'Example',
    'deploymentPath' => '/var/www/example.com/surf',
    'phpBinaryPathAndFilename' => '/usr/bin/php7.4',
    'adminMail' => 'deployment@example.de',

    'hostname' => '',
    'username' => '',
    'password' => '',
    'port' => '',

    'repositoryUrl' => 'https://github.com/Example/Example.git',
    'branch' => 'staging',
    'doUpgrade' => false,
    'queryFileBeforeUpgrade' => '',
    'queryFileAfterUpgrade' => ''
];
```
## File: _.htaccess.dev / _.htaccess.prod / _.htaccess.stage / config.nginx.dev / config.nginx.prod / config.nginx.stage
Contains the settings for the given environment. You can use .htaccess for Apache or config.nginx for Nginx.
Will be deployed automatically.

## File: _.htpasswd.dev / _.htpasswd.prod / _.htpasswd.stage
Same as _htaccess.*

## File: composer.json
Contains the packages to install.
To be able to install packages that themselves have dependencies on packages that are only available as @dev, you need to specify the `minimum-stabilty` in combination with `prefer-stable`.
With `preferred-install` you can specify that certain packages should be installed as GIT repositories so that you can work on them directly.

## Folder: dummy
Contains dummy files for sys_file-references.
The content of this folder will be deployed on your stage-environment (of course not on production!)

## File: _.env.dev / _.env.prod / _.env.stage
This file contains a list of all extensions to be activated in the given enviroment.
If this file is copied to `.env` before installation, the package "helhum/dotenv-connector" will automatically create a corresponding `PackageStates.php`.

Example:
```
# Context
TYPO3_CONTEXT="Production"

# A set of TYPO3 framework extensions (delivered within typo3/cms), which should be marked as active
TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS="about,backend,belog,beuser,context_help,core,cshmanual,extbase,extensionmanager,felogin,filelist,filemetadata,fluid,fluid_styled_content,frontend,func,impexp,info,info_pagetsconfig,install,lang,lowlevel,recordlist,recycler,reports,rsaauth,rte_ckeditor,saltedpasswords,scheduler,setup,sv,sys_action,sys_note,taskcenter,tstemplate,version,viewpage,wizard_crpages,wizard_sortpages"
```

## File: web/typo3conf/AdditionalConfiguration.dev.php / AdditionalConfiguration.prod.php / AdditionalConfiguration.stage.php
This file contains the relevant settings for the according environment.
**IMPORTANT: Do NOT put any access data or enycryption keys into versioning that are relevant for the live environment. These are ONLY to be put into `AdditionConfiguation.php` on production and stage respectively!!!**

# How to deploy
For the deployment you need a branch with the same name as the deployment-step you want to execute.

Examples:
- If you want to deploy into the staging-enviroment you have to push everything to the `staging`-branch.
- If you want to deploy into the production-enviroment you have to push everything to the `production`-branch.

You also need a **deployment script** with the same name as the branch you want to deploy, e.g `./.surf/Staging.php` for `staging`-branch.

Beyond that it is necessary to create a corresponding **credential file** in .`surf/Credentials`. Use the example above as template.

## Before you deploy
In case there are any uncommitted changes, please commit them in the corresponding repository to prevent losing them when calling composer throughout the deployment process.

In case you may need a new extension, please don't forget to publish it on packagist. Otherwise it won't be available to standard composer update process.

- In order to make your changes effective, the `composer.lock` has to get the new version information. So in your website-repository do a
```
vm$ composer update
```
- The new `composer.lock` has to be committed as well. So you need to push the changes of your website-repository before executing a deployment.

## The deployment

Do the deployment using the following command from your DocumentRoot.

**IMPORTANT: The surf extension requires PHP 7 on the CLI**
**IMPORTANT: Do NOT run deployment with `root` or super-user !!! Always use your local user (e.g. `vagrant`)**

**IMPORTANT: Please login to the TYPO3 backend of your target installation (Staging, Production, ...), so you may have access to it, even if anything may fail or break during the deployment with .surf.**

```
vm$ php ./vendor/typo3/surf/surf deploy <DEPLOYMENT-FILE>
vm$ php ./vendor/typo3/surf/surf deploy Staging
```

You can use verbose-output to get more information if something goes wrong:
```
vm$ php ./vendor/typo3/surf/surf deploy Staging -v
vm$ php ./vendor/typo3/surf/surf deploy Staging -vv
vm$ php ./vendor/typo3/surf/surf deploy Staging -vvv
```

# Important hints
* Make sure PHP APCU is installed
 ```
apt-get install php7.4-apc php7.4-apcu
 ```
## When running with doUpgrade
* Before running the deployment with `doUpgrade = true` make sure your MySQL-user has the right privileges to execute a mysldump:
 ```
GRANT RELOAD, PROCESS ON *.* TO 'my-user'@'%';
FLUSH PRIVILEGES;
 ```
* If you have an external server to access, you need to at least install mysql-client
 ```
apt-get mysql-client
 ```
* If you are trying to access an older MySQL-version via MySQL-Client v8+, you should add this line
in your `/etc/mysql/my.cnf`
```
[mysqldump]
column-statistics=0
 ```
