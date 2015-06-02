<?php

namespace HF\POOL\Entity;

/**
 * RecordLock
 */
class RecordLock
{
    /**
     * @var string
     */
    private $object_type;

    /**
     * @var string
     */
    private $object_key;

    /**
     * @var string
     */
    private $user_ident;

    /**
     * @var integer
     */
    private $lock_obtained;

    /**
     * @var integer
     */
    private $lock_ttl;

    /**
     * @var string
     */
    private $reason;

    /**
     * @param string $objectType
     * @param array string $objectKey
     */
    public function __construct($objectType, $objectKey)
    {
        $this->object_type = $objectType;
        $this->object_key  = $objectKey;
    }

    /**
     * Get objectType
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->object_type;
    }

    /**
     * Get objectKey
     *
     * @return string
     */
    public function getObjectKey()
    {
        return $this->object_key;
    }

    /**
     * Set userIdent
     *
     * @param string $userIdent
     */
    public function setUserIdent($userIdent)
    {
        $this->user_ident = $userIdent;
    }

    /**
     * Get userIdent
     *
     * @return string
     */
    public function getUserIdent()
    {
        return $this->user_ident;
    }

    /**
     * @return integer
     */
    public function getLockObtained()
    {
        return $this->lock_obtained;
    }

    /**
     * @param integer $lock_obtained
     */
    public function setLockObtained($lock_obtained)
    {
        $this->lock_obtained = $lock_obtained;
    }

    /**
     * @return int
     */
    public function getLockTtl()
    {
        return $this->lock_ttl;
    }

    /**
     * @param int $lock_ttl
     */
    public function setLockTtl($lock_ttl)
    {
        $this->lock_ttl = $lock_ttl;
    }

    /**
     * Set reason
     *
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

}

