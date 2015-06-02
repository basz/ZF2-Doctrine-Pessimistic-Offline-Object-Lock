<?php

namespace HF\POOL\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use HF\POOL\Exception;

class DoctrinePOOLManager implements POOLManagerInterface, DoctrinePOOLManagerInterface
{

    /**
     * @var POOLManagerInterface
     */
    private $poolManager;

    /**
     * @param POOLManagerInterface $poolManager
     */
    public function __construct(POOLManagerInterface $poolManager)
    {
        $this->poolManager = $poolManager;
    }

    /**
     * Proxies to decorated poolManager
     *
     * @inheritdoc
     */
    public function acquireLock($objectType, $objectKey, $ttl = null, $reason = null)
    {
        return $this->poolManager->acquireLock($objectType, $objectKey, $ttl, $reason);
    }

    /**
     * Proxies to decorated poolManager
     *
     * @inheritdoc
     */
    public function relinquishLock($objectType, $objectKey)
    {
        return $this->poolManager->relinquishLock($objectType, $objectKey);
    }

    /**
     * Proxies to decorated poolManager
     *
     * @inheritdoc
     */
    public function relinquishExpiredLocks($ttl = null, $objectKey = null, $userIdent = null)
    {
        return $this->poolManager->relinquishExpiredLocks($ttl, $objectKey, $userIdent);
    }

    /**
     * Proxies to decorated poolManager
     *
     * @inheritdoc
     */
    public function getLockInfo($objectType, $objectKey)
    {
        return $this->poolManager->getLockInfo($objectType, $objectKey);
    }

    /**
     * @inheritdoc
     *
     * @todo Create exception in namespace
     * @todo Cache em::getClassMetadata as that method is marked as performance sensitive
     */
    public function toTypeKey($entity, EntityManager $entityManager)
    {
        if (!is_object($entity)) {
            throw new \InvalidArgumentException("Argument must be an object for " . __CLASS__);
        }

        /** @var ClassMetadataInfo $metaInfo */
        $metaInfo         = $entityManager->getClassMetadata(get_class($entity));
        $identifierValues = $metaInfo->getIdentifierValues($entity);

        $objectKey = implode(":", array_values($identifierValues));

        return [$metaInfo->getName(), $objectKey];
    }
}