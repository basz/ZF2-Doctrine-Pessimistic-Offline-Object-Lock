<?php

namespace HFTest\POOL\Framework;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
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

    public function setupFixtures($dataSet)
    {
        foreach ($dataSet as $dataRow) {
            $this->getObjectManager()->getConnection()->executeUpdate(
                'INSERT INTO `recordlock` (object_type, object_key, user_ident, lock_obtained, lock_ttl, reason) ' .
                'VALUES (?, ?, ?, ?, ?, ?)',
                $dataRow,
                [Type::STRING, Type::STRING, Type::STRING, Type::INTEGER, Type::INTEGER, Type::STRING]
            );
        }

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