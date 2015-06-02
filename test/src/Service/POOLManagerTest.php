<?php

namespace HFTest\POOL\Service;

use HF\POOL\Service\POOLManager;
use HFTest\POOL\Bootstrap;
use HFTest\POOL\Framework\BaseTestCase;

class POOLManagerTest extends BaseTestCase
{

    /**
     * @var POOLManager
     */
    protected $poolManager;

    public function setUp()
    {
        parent::setUp();

        $entity = new \HFTest\POOL\Entity\SInglePKEntity();
        $entity->setName('foo');
        $this->getObjectManager()->persist($entity);
        $entity = new \HFTest\POOL\Entity\SInglePKEntity();
        $entity->setName('bar');
        $this->getObjectManager()->persist($entity);

        $entity = new \HFTest\POOL\Entity\CompositedPKEntity(1, 2);
        $this->getObjectManager()->persist($entity);
        $entity = new \HFTest\POOL\Entity\CompositedPKEntity(3, '4');
        $this->getObjectManager()->persist($entity);

        $this->getObjectManager()->flush();
die();
        $this->poolManager = Bootstrap::getServiceManager()->get('hf_pool.manager');

        die();
    }

    /**
     * Test acquireLock method
     */
    public function testAcquiringALock()
    {
        $success = $this->poolManager->acquireLock('object', 'pk', 'me');
        $this->assertTrue($success);

        $locks = $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll();

        $this->assertCount(1, $locks);

        /** @var \HF\POOL\Entity\RecordLock $lock */
        $lock = array_pop($locks);

        $this->assertEquals('object', $lock->getObjectType());
        $this->assertEquals('pk', $lock->getObjectKey());
        $this->assertEquals('me', $lock->getUserIdent());
        $this->assertNull($lock->getLockTtl());
        $this->assertNull($lock->getReason());
    }

    /**
     * Test re-acquiring a lock
     */
    public function testAcquiringLockOnAlreadyAcquiredLock()
    {
        $this->loadFixtures([
            ['object', 'pk1', 'someone', time() - 10000, null, null], // expired
            ['object', 'pk2', 'someone', time() - 10, null, null], // valid
            ['object', 'pk3', 'me', time() - 10000, null, null], // expired
            ['object', 'pk4', 'me', time() - 10, null, null], // valid
        ]);

        $acquired = $this->poolManager->acquireLock('object', 'pk1', 'me');
        $this->assertTrue($acquired);
        $acquired = $this->poolManager->acquireLock('object', 'pk2', 'me');
        $this->assertFalse($acquired);
        $acquired = $this->poolManager->acquireLock('object', 'pk3', 'me');
        $this->assertTrue($acquired);
        $acquired = $this->poolManager->acquireLock('object', 'pk4', 'me');
        $this->assertTrue($acquired);



//        $acquired = $this->poolManager->acquireLock('object', 'pk2', 'me');
//        $this->assertTrue($acquired);
//
//        $locks = $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll();
//
//        $this->assertCount(1, $locks);
//
//        /** @var \HF\POOL\Entity\RecordLock $lock */
//        $lock = array_pop($locks);
//
//        $this->assertEquals('object', $lock->getObjectType());
//        $this->assertEquals('pk', $lock->getObjectKey());
//        $this->assertEquals('me', $lock->getUserIdent());
//        $this->assertNull($lock->getLockTtl());
//        $this->assertNull($lock->getReason());
    }

    /**
     * Test that for existing locks the ttl and/or reason is preserved while reacquiring a lock. but can be overridden
     * with new values
     */
    public function testReacquiringALockWithNewTtlOrReason()
    {
        // acquire lock with a ttl and reason
        $this->poolManager->acquireLock('object', 'pk', 'me', 10, 'because');

        $locks = $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll();

        /** @var \HF\POOL\Entity\RecordLock $lock */
        $lock = array_pop($locks);

        $this->assertEquals(10, $lock->getLockTtl());
        $this->assertEquals('because', $lock->getReason());

        // re-acquires lock without optionals
        $acquired = $this->poolManager->acquireLock('object', 'pk', 'me');
        $this->assertTrue($acquired);

        $this->getObjectManager()->clear();
        $locks = $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll();

        /** @var \HF\POOL\Entity\RecordLock $lock */
        $lock = array_pop($locks);

        $this->assertEquals(10, $lock->getLockTtl());
        $this->assertEquals('because', $lock->getReason());
    }

    /**
     * Test acquiring a lock on an already locked object by different user ident
     */
    public function testAcquiringLockOnAlreadyAcquiredLockByDifferentUserIdent()
    {
        $acquired = $this->poolManager->acquireLock('object', 'pk', 'me');
        $this->assertTrue($acquired);
        $acquired = $this->poolManager->acquireLock('object', 'pk', 'you');
        $this->assertFalse($acquired);
    }

    /**
     * Test relinquish a lock
     */
    public function testRelinquishLock()
    {
        $this->loadFixtures([
            ['object', 'key1', 'me', time(), null, null], // valid
            ['object', 'key2', 'me', time() - 3600, null, null], // expired
        ]);

        $relinquished = $this->poolManager->relinquishLock('object', 'key1');
        $this->assertTrue($relinquished);
        $relinquished = $this->poolManager->relinquishLock('object', 'key2');
        $this->assertFalse($relinquished);
        $this->assertCount(1,
            $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll());
    }

    /**
     * Test relinquishing a non existent object lock
     */
    public function testRelinquishNonExistentLock()
    {
        $relinquished = $this->poolManager->relinquishLock('object', 'key');
        $this->assertFalse($relinquished);
    }

    /**
     * Test getLockInfo
     */
    public function testGetLockInfo()
    {
        $this->loadFixtures([
            ['object', 'key1', 'me1', time() - 10, 9, null], // expired
            ['object', 'key2', 'me2', time() - 10, 10, null], // valid
            ['object', 'key3', 'me3', time() - 10, 11, null], // valid
            ['object', 'key4', 'me4', time() - 3600, null, null], // expired
            ['object', 'key5', 'me5', time() - 360, null, null], // valid
            ['object', 'key6', 'me6', time() - 36, null, null], // valid
        ]);

        $this->assertFalse($this->poolManager->getLockInfo('object', 'key1'));
        $this->assertFalse($this->poolManager->getLockInfo('object', 'key4'));

        $this->assertEquals([
            'userIdent'  => 'me2',
            'ttl'        => 0,
            'reason'     => null,
            'objectType' => 'object',
            'objectKey'  => 'key2'
        ], $this->poolManager->getLockInfo('object', 'key2'));

        $this->assertEquals([
            'userIdent'  => 'me3',
            'ttl'        => 1,
            'reason'     => null,
            'objectType' => 'object',
            'objectKey'  => 'key3'
        ], $this->poolManager->getLockInfo('object', 'key3'));

        $this->assertEquals([
            'userIdent'  => 'me5',
            'ttl'        => 540,
            'reason'     => null,
            'objectType' => 'object',
            'objectKey'  => 'key5'
        ], $this->poolManager->getLockInfo('object', 'key5'));

        $this->assertEquals([
            'userIdent'  => 'me6',
            'ttl'        => 864,
            'reason'     => null,
            'objectType' => 'object',
            'objectKey'  => 'key6'
        ], $this->poolManager->getLockInfo('object', 'key6'));
    }

    /**
     * Test retrieving the user ident of a particular object lock
     */
    public function testGetLockInfoOfExpiredLockReturnsFalse()
    {
        $this->loadFixtures([
            ['object', 'key1', 'me1', time() - 10, 9, null], // expired
            ['object', 'key2', 'me2', time() - 10, 10, null], // valid
            ['object', 'key3', 'me3', time() - 10, 11, null], // valid
            ['object', 'key4', 'me1', time() - 3600, null, null], // expired
            ['object', 'key5', 'me2', time() - 360, null, null], // valid
            ['object', 'key6', 'me3', time() - 36, null, null], // valid
        ]);

        $this->assertFalse($this->poolManager->getLockInfo('object', 'key1'));
        $this->assertNotFalse($this->poolManager->getLockInfo('object', 'key2'));
        $this->assertNotFalse($this->poolManager->getLockInfo('object', 'key3'));
        $this->assertFalse($this->poolManager->getLockInfo('object', 'key4'));
        $this->assertNotFalse($this->poolManager->getLockInfo('object', 'key5'));
        $this->assertNotFalse($this->poolManager->getLockInfo('object', 'key6'));
    }

    public function dpRelinquishExpiredLocks()
    {
        // test set array of arguments passed to the method being tested and then array of object
        // that we expect to be removed from the db
        // [age, objectType, objectKey], [objectType:objectKey] */

        return [
            [[null, null, null], ['a:1', 'b:2', 'b:6', 'a:7']],
            [[600, null, null], ['a:1', 'b:2', 'a:3', 'b:6', 'a:7', 'b:8']],
            [[0, null, null], ['a:1', 'b:2', 'a:3', 'b:4', 'a:5', 'b:6', 'a:7', 'b:8', 'a:9', 'b:10']],
            [[null, 'a', null], ['a:1', 'a:7']],
            [[600, 'b', null], ['b:2', 'b:6', 'b:8']],
            [[0, 'a', null], ['a:1', 'a:3', 'a:5', 'a:7', 'a:9']],
            [[0, null, 'him'], ['a:1', 'b:4', 'b:10']],
            [[0, null, 'her'], ['b:2', 'a:5', 'b:8']],
            [[0, null, 'she'], ['a:3', 'b:6', 'a:9']],
            [[0, 'a', 'him'], ['a:1']],
            [[0, 'a', 'her'], ['a:5']],
            [[0, 'a', 'she'], ['a:3', 'a:9']],
        ];
    }

    /**
     * @dataProvider dpRelinquishExpiredLocks
     */
    public function testRelinquishExpiredLocks($arguments, $locksRemoved)
    {
        $this->loadFixtures([
            ['a', '1', 'him', '20 minutes ago', null, null],
            ['b', '2', 'her', '15 minutes ago', null, null],
            ['a', '3', 'she', '10 minutes ago', null, null],
            ['b', '4', 'him', '5 minutes ago', null, null],
            ['a', '5', 'her', '0 minutes ago', null, null],
            ['b', '6', 'she', '20 minutes ago', 60 * 10, null],
            ['a', '7', 'him', '15 minutes ago', 60 * 10, null],
            ['b', '8', 'her', '10 minutes ago', 60 * 10, null],
            ['a', '9', 'she', '5 minutes ago', 60 * 10, null],
            ['b', '10', 'him', '0 minutes ago', 60 * 10, null],
        ]);

        call_user_func_array([$this->poolManager, 'relinquishExpiredLocks'], $arguments);

        $assertAllLocksAreRemoved = function () use ($locksRemoved) {
            $locks = $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll();
            /** @var \HF\POOL\Entity\RecordLock $lock */
            foreach ($locks as $lock) {
                $check = sprintf('%s:%s', $lock->getObjectType(), $lock->getObjectKey());

                $this->assertNotContains($check, $locksRemoved, sprintf("%s was not deleted", $check));
                if (in_array($check, $locksRemoved)) {
                    return false;
                }
            }

            return true;
        };

        $this->assertTrue($assertAllLocksAreRemoved());
    }

}