<?php

namespace HF\POOL\Service;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;

interface DoctrinePOOLManagerInterface
{
    /**
     * @param object        $entity
     * @param EntityManager $entityManager EntityManager that manages the entity
     * @return array [$objectType, $objectKey]
     * @throws MappingException when the entity manager doesn't manages the entity
     */
    public function toTypeKey($entity, EntityManager $entityManager);
}