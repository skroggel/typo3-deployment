<?php
namespace Madj2k\TYPO3Deployment\Domain\Model;

/**
 * Class Node
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Madj2k
 * @package Madj2k_T3Deployment
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later */

class Node extends \TYPO3\Surf\Domain\Model\Node
{

    /**
     * Constructor
     * @param string $name
     * @return void
     */
    public function __construct(string $name = 'Server')
    {
        parent::__construct($name);
        $this->options = array_merge($this->options, array(
            'hostname' => '',
            'username' => '',
            'password' => '',
            'port' => ''
        ));
    }


    /**
     * Init node with params
     *
     * @param array $options
     * @throws \Madj2k\Deployment\TYPO3\Exception
     * @return void
     */
    public function initNode(array $options): void
    {

        // set all options
        $mergedOptions = array_merge($this->options, $options);
        foreach (array_keys($this->options) as $key) {

            if (! isset($mergedOptions[$key])){
                throw new \Madj2k\Deployment\TYPO3\Exception(sprintf('Param "%s" has not been set.', $key));
            }
            $this->setOption($key, $mergedOptions[$key]);

            // override name
            if ($key == 'hostname') {
                $this->setName($mergedOptions[$key]);
            }
        }
    }

}
