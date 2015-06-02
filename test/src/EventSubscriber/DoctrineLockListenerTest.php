<?php

namespace HFTest\POOL\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use HF\POOL\EventSubscriber\DoctrineLockListener;
use HF\POOL\Provider\UserId\UserIdProviderInterface;
use HF\POOL\Service\DoctrinePOOLManager;
use HFTest\POOL\Entity\SinglePKEntity;
use HFTest\POOL\Framework\BaseTestCase;

class DoctrineLockListenerTest extends BaseTestCase
{
    /**
     * @var DoctrineLockListener
     */
    protected $listener;

    public function setUp()
    {
        $poolManager    = $this->getMockBuilder(DoctrinePOOLManager::class)->disableOriginalConstructor()->getMock();
        $userIdProvider = $this->getMockBuilder(UserIdProviderInterface::class)->getMock();
        $poolManager->method('toTypeKey')->willReturn(['type', '2']);
        $this->listener = new DoctrineLockListener($poolManager, $userIdProvider);
    }

    public function tearDown()
    {

    }

    public function testGetSubscribedEvents()
    {
        $this->assertContains(Events::onFlush, $this->listener->getSubscribedEvents());
    }

    public function testThis()
    {
        $unitOfWork    = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $argsEvent     = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
        $argsEvent->method('getEntityManager')->willReturn($entityManager);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        $this->listener->onFlush($argsEvent);

//        $entity = new CompositedPKEntity('a', 'b');
//
//        /** @var AuthenticationService $auth */
//        $auth = Bootstrap::getServiceManager()->get('hf_pool.authenticationService');
//        $auth->getStorage()->write('you');
//
//        $this->getObjectManager()->persist($entity);
//        $this->getObjectManager()->flush($entity);
//
//        // create a lock
//        $this->objectLockManager->acquireLock(get_class($entity), 'a:b', 'me');
//
//        $entity->setName('bar');
//
//        $this->getObjectManager()->persist($entity);
//
//        $this->setExpectedException(LockedException::class);
//        $this->getObjectManager()->flush($entity);

    }
}