<?php

namespace HFTest\POOL\Service;

use HF\POOL\Entity\RecordLock;
use HFTest\POOL\Framework\BaseTestCase;

class ObjectLockManagerTest extends BaseTestCase
{

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
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test acquireLock method
     */
    public function testAcquiringALock()
    {
        $success = $this->objectLockManager->acquireLock('object', 'pk', 'me');
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
        $acquired = $this->objectLockManager->acquireLock('object', 'pk', 'me');
        $this->assertTrue($acquired);
        $acquired = $this->objectLockManager->acquireLock('object', 'pk', 'me');
        $this->assertTrue($acquired);

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
     * Test that for existing locks the ttl and/or reason is preserved while reacquiring a lock. but can be overridden
     * with new values
     */
    public function testReacquiringALockWithNewTtlOrReason()
    {
        // acquire lock with a ttl and reason
        $this->objectLockManager->acquireLock('object', 'pk', 'me', 10, 'because');

        $locks = $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll();

        /** @var \HF\POOL\Entity\RecordLock $lock */
        $lock = array_pop($locks);

        $this->assertEquals(10, $lock->getLockTtl());
        $this->assertEquals('because', $lock->getReason());

        // re-acquires lock without optionals
        $acquired = $this->objectLockManager->acquireLock('object', 'pk', 'me');
        $this->assertTrue($acquired);

        $this->getObjectManager()->clear();
        $locks = $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll();

        /** @var \HF\POOL\Entity\RecordLock $lock */
        $lock = array_pop($locks);

//        $this->assertGreaterThanOrEqual(10, $lock->getLockUntil()->format('U') - time());
//        $this->assertEquals('some-reason', $lock->getReason());
        $this->assertEquals(10, $lock->getLockTtl());
        $this->assertEquals('because', $lock->getReason());
    }

    /**
     * Test acquiring a lock on an already locked object by different user ident
     */
    public function testAcquiringLockOnAlreadyAcquiredLockByDifferentUserIdent()
    {
        $acquired = $this->objectLockManager->acquireLock('object', 'pk', 'me');
        $this->assertTrue($acquired);
        $acquired = $this->objectLockManager->acquireLock('object', 'pk', 'you');
        $this->assertFalse($acquired);
    }

    /**
     * Test relinquish a lock
     */
    public function testRelinquishLock()
    {
        // first acquire a lock
        $acquired = $this->objectLockManager->acquireLock('object', 'key', 'me');
        $this->assertTrue($acquired);

        $relinquished = $this->objectLockManager->relinquishLock('object', 'key');
        $this->assertTrue($relinquished);
        $this->assertCount(0,
            $this->getObjectManager()->getRepository(\HF\POOL\Entity\RecordLock::class)->findAll());
    }

    /**
     * Test relinquishing a non existent object lock
     */
    public function testRelinquishNonExistentLock()
    {
        $relinquished = $this->objectLockManager->relinquishLock('object', 'key');
        $this->assertFalse($relinquished);
    }

    /**
     * Test retrieving the user ident of a particular object lock
     */
    public function testGetUserIdentOfLock()
    {
        $this->assertFalse($this->objectLockManager->getUserIdent('object', 'key'));

        // first acquire a lock
        $acquired = $this->objectLockManager->acquireLock('object', 'key', 'me');
        $this->assertTrue($acquired);

        $this->assertEquals('me', $this->objectLockManager->getUserIdent('object', 'key'));
    }

    /**
     * Test retrieving the user ident of a particular object lock
     */
    public function testGetUserIdentOfExpiredLockReturnsFalse()
    {
        $this->assertFalse($this->objectLockManager->getUserIdent('object', 'key'));

        // first acquire a lock
        $acquired = $this->objectLockManager->acquireLock('object', 'key', 'me', -100); // note expired allready!
        $this->assertTrue($acquired);

        $this->assertFalse($this->objectLockManager->getUserIdent('object', 'key'));
    }

    /**
     * add fixtures
     */
    public function setupRelinquishAgedLocks()
    {
        $createLockObject = function ($objectTypeKey, $userIdent, $lockObtained, $lockTtl) {
            list($objectType, $objectKey) = explode(':', $objectTypeKey);
            $lock = new RecordLock($objectType, $objectKey);
            $lock->setUserIdent($userIdent);
            $lock->setLockObtained(new \DateTime($lockObtained));
            $lock->setLockTtl($lockTtl);

            return $lock;
        };

        $fixtureData = [
            ['a:1', 'him', '20 minutes ago', null],
            ['b:2', 'her', '15 minutes ago', null],
            ['a:3', 'she', '10 minutes ago', null],
            ['b:4', 'him', '5 minutes ago', null],
            ['a:5', 'her', '0 minutes ago', null],
            ['b:6', 'she', '20 minutes ago', 60*10],
            ['a:7', 'him', '15 minutes ago', 60*10],
            ['b:8', 'her', '10 minutes ago', 60*10],
            ['a:9', 'she', '5 minutes ago', 60*10],
            ['b:10', 'him', '0 minutes ago', 60*10],
        ];

        foreach ($fixtureData as $fData) {
            $lock = call_user_func_array($createLockObject, $fData);
            $this->getObjectManager()->persist($lock);
        }

        $this->getObjectManager()->flush();
        $this->getObjectManager()->clear();
    }

    public function dpRelinquishAgedLocks()
    {
        // test set array of arguments passed to the method being tested and then array of object
        // that we expect to be removed from the db
        // [age, objectType, objectKey], [objectType:objectKey] */

        return [
            [[null, null, null], ['a:1', 'b:2', 'b:6', 'a:7']],
            [[600, null, null],  ['a:1', 'b:2', 'a:3', 'b:6', 'a:7', 'b:8']],
            [[0, null, null],    ['a:1', 'b:2', 'a:3', 'b:4', 'a:5', 'b:6', 'a:7', 'b:8', 'a:9', 'b:10']],

            [[null, 'a', null], ['a:1', 'a:7']],
            [[600, 'b', null],  ['b:2', 'b:6', 'b:8']],
            [[0, 'a', null],    ['a:1', 'a:3', 'a:5', 'a:7', 'a:9']],

            [[0, null, 'him'],    ['a:1', 'b:4', 'b:10']],
            [[0, null, 'her'],    ['b:2', 'a:5', 'b:8']],
            [[0, null, 'she'],    ['a:3', 'b:6', 'a:9']],

            [[0, 'a', 'him'],    ['a:1']],
            [[0, 'a', 'her'],    ['a:5']],
            [[0, 'a', 'she'],    ['a:3', 'a:9']],
        ];
    }

    /**
     * @dataProvider dpRelinquishAgedLocks
     */
    public function testRelinquishAgedLocks($arguments, $locksRemoved)
    {
        // loads fixtures
        $this->setupRelinquishAgedLocks();

        call_user_func_array([$this->objectLockManager, 'relinquishLocks'], $arguments);

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