<?php
namespace Madj2k\SurfDeployment;

/**
 * Class Deployment
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_T3Deployment
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @var \TYPO3\Surf\Domain\Model\Deployment $deployment
 */

use Madj2k\SurfDeployment\Domain\Model\Application;
use Madj2k\SurfDeployment\Domain\Model\Node;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\StreamOutput;

class Deployment
{
    /**
     *
     * Basic application specific options
     * @var array
     */
    protected $options = array(
        'workspacesBasePath' => '/tmp/surf'
    );


    /**
     * Deployment constructor
     *
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param $options
     * @throws \Madj2k\SurfDeployment\Exception
     */
    public function __construct(\TYPO3\Surf\Domain\Model\Deployment $deployment, $options)
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
        }

        // security question
        $question = new ConfirmationQuestion(
            'Continue with deployment of branch [' . $options['branch']
            . '] on server [' . $options['hostname'] . "]?\n(y|n) ",
            false,
            '/^(y|j)/i'
        );
        $helper = new QuestionHelper;
        $input = new ArgvInput;
        $output = new StreamOutput(fopen('php://stdout', 'w'));

        if (!$helper->ask($input, $output, $question)) {
            exit;
        }

        $application = new Application();
        $application->initApplication($options);

        $node = new Node();
        $node->initNode($options);
        $application->addNode($node);

        if ($this->options['workspacesBasePath']) {
            $deployment->setWorkspacesBasePath($this->options['workspacesBasePath']);
        }

        $deployment->addApplication($application);
    }
}

