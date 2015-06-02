<?php

namespace HF\POOL\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use HF\POOL\Exception\LockedException;
use HF\POOL\Provider\UserId\UserIdProviderInterface;
use HF\POOL\Service\DoctrinePOOLManager;
use Zend\Authentication\AuthenticationServiceInterface;

class DoctrineLockListener implements EventSubscriber
{
    /**
     * @var DoctrinePOOLManager
     */
    private $poolManager;

    /**
     * @var UserIdProviderInterface
     */
    private $userIdProvider;

    /**
     * @param DoctrinePOOLManager   $poolManager
     * @param UserIdProviderInterface $authenticationService
     */
    public function __construct(
        DoctrinePOOLManager $poolManager,
        UserIdProviderInterface $userIdProvider
    ) {
        $this->poolManager           = $poolManager;
        $this->userIdProvider = $userIdProvider;
    }

    /**
     * List of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    /**
     * onFlush event handler
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->checkLock($args->getEntityManager(), $entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->checkLock($args->getEntityManager(), $entity);
        }
    }

    /**
     * Informs with the poolManager the object if a lock exists
     *
     * @param               $entity
     * @throws LockedException when an lock exists by any other user then the authenticated user
     */
    private function checkLock(EntityManager $entityManager, $entity)
    {
        list($objectType, $objectKey) = $this->poolManager->toTypeKey($entity, $entityManager);

        if ($lockInfo = $this->poolManager->getLockInfo($objectType, $objectKey)) {
            if (($userIdent = $this->userIdProvider->getId()) && $userIdent != $lockInfo['userIdent']) {
                throw new LockedException($lockInfo);
            }
        }
    }
}
