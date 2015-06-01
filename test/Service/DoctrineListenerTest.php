<?php

namespace HFTest\POOL\Service;

use HF\POOL\Exception\LockedException;
use HFTest\POOL\Bootstrap;
use HFTest\POOL\Entity\CompositedPKEntity;
use HFTest\POOL\Framework\BaseTestCase;
use Zend\Authentication\AuthenticationService;

class DoctrineListenerTest extends BaseTestCase
{

    public function testThis()
    {
        $entity = new CompositedPKEntity('a', 'b');

        /** @var AuthenticationService $auth */
        $auth = Bootstrap::getServiceManager()->get('hf_pool.authenticationService');
        $auth->getStorage()->write('you');

        $this->getObjectManager()->persist($entity);
        $this->getObjectManager()->flush();

        // create a lock
        $this->objectLockManager->acquireLock(get_class($entity), 'a:b', 'me');

        $entity->setName('bar');

        $this->getObjectManager()->persist($entity);

        $this->setExpectedException(LockedException::class);
        $this->getObjectManager()->flush();

    }
}