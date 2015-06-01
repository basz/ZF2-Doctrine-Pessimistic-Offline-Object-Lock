<?php

namespace HFTest\POOL\Framework;

use Doctrine\Common\Persistence\ObjectManager;
use HF\POOL\Service\ObjectLockManager;
use HFTest\POOL\Bootstrap;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ObjectLockManager
     */
    protected $objectLockManager;

    public function setUp()
    {
        $command = new StringInput('orm:schema-tool:create');
        Bootstrap::getServiceManager()->get('doctrine.cli')->run($command, new NullOutput());

        $this->objectLockManager = Bootstrap::getServiceManager()->get('hf_pool.manager');
    }

    public function tearDown()
    {
        $command = new StringInput('orm:schema-tool:drop --force');
        Bootstrap::getServiceManager()->get('doctrine.cli')->run($command, new NullOutput());

        $this->getObjectManager()->clear();
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        if ($this->objectManager === null) {
            $this->objectManager = Bootstrap::getServiceManager()->get('doctrine.entity_manager.orm_default');
        }

        return $this->objectManager;
    }
}