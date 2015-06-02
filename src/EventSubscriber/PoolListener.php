<?php

namespace HF\POOL\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use HF\POOL\Exception\LockedException;
use HF\POOL\Service\ObjectLockManager;
use Zend\Authentication\AuthenticationServiceInterface;

class PoolListener implements EventSubscriber
{
    /**
     * @var ObjectLockManager
     */
    private $objectLockManager;

    /**
     * @var AuthenticationServiceInterface
     */
    private $authenticationService;

    private $em;


    public function __construct(
        ObjectLockManager $objectLockManager,
        AuthenticationServiceInterface $authenticationService
    ) {
        $this->objectLockManager     = $objectLockManager;
        $this->authenticationService = $authenticationService;
    }

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            // Events::preUpdate,
            // Events::preRemove,
            Events::onFlush
        ];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $uow = $this->em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this($entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this($entity);
        }

    }

    /**
     * The preUpdate event occurs before the database update operations to entity data. It is not called for a DQL
     * UPDATE statement.
     *
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->em = $args->getObjectManager();
        $this($args->getObject());
    }

    /**
     * The preRemove event occurs for a given entity before the respective EntityManager remove operation for that
     * entity is executed. It is not called for a DQL DELETE statement.
     *
     * The preRemove event is called on every entity when its passed to the EntityManager#remove() method. It is
     * cascaded for all associations that are marked as cascade delete.
     *
     * There are no restrictions to what methods can be called inside the preRemove event, except when the remove
     * method itself was called during a flush operation.
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->em = $args->getObjectManager();
        $this($args->getObject());
    }

    /**
     *
     * @param LifecycleEventArgs $args
     */
    public function __invoke($object)
    {
        if (!$this->authenticationService->hasIdentity()) {
            return;
        }

        /** @var $meta ClassMetadataInfo */
        $objectType       = get_class($object);
        $identifierValues = $this->em->getClassMetadata($objectType)->getIdentifierValues($object);
        $objectKey        = implode(":", array_values($identifierValues));

        // will return a user ident if a valid lock exists
        if ($lockInfo = $this->objectLockManager->getLockInfo($objectType, $objectKey)) {
            if ($lockInfo['user_ident'] !== $this->authenticationService->getIdentity()) {
                throw new LockedException(sprintf(LockedException::MESSAGE_LOCKED, $objectType, $objectKey));
            }
        }
    }
}

