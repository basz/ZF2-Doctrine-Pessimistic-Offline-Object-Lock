<?php

namespace HF\POOL\Service;

use HF\POOL\Exception;

interface POOLManagerInterface
{

    /**
     * Locks an object for a specific user identity. When a lock already exists it is renewed while preserving the
     * ttl/reason values unless overwritten with new ones
     *
     * @param string       $objectType  the type of object
     * @param string       $objectKey   the primary key of the object
     * @param null|integer $ttl         optional time in seconds for which the object should stay locked from the moment
     *                                  the object is locked
     * @param null         $reason      optional description of why the lock was acquired (e.g. editing, processing)
     * @return bool true on (re)locking, false when no lock was acquired (because it was already locked)
     * @throws Exception\RuntimeException
     */
    public function acquireLock($objectType, $objectKey, $ttl = null, $reason = null);

    /**
     * Relinguish a specific lock
     *
     * @param string $objectType the type of object
     * @param string $objectKey  the primary key of the object
     * @return boolean True if a *valid* lock was relinquished
     * @throws Exception\RuntimeException
     */
    public function relinquishLock($objectType, $objectKey);

    /**
     * Deletes locks considered expired, filtered by object type and/or user ident.
     *
     * To consider locks expired we compare the time the lock was obtained with the ttl.
     *
     * The ttl used in the calculations come one of the following;
     *
     * - from the a lock_ttl field saved with the record
     *   when present it is compared to the given ttl given as argument.
     * - from the ttl argument given to this method
     * - the default from the options
     *
     * example : time is now 9:00, default ttl = 900
     *
     * record [obtained - ttl] - argument [ttl]
     *         9:00       null             null -> would be kept
     *         9:00       60               null -> would be kept
     *         9:00       null             0    -> would be deleted
     *         8:00       null             null -> would be deleted
     *         8:45       null             null -> would be deleted
     *         8:50       null             null -> would be kept
     *         8:50       60               null -> would be deleted
     *         8:50       null             null -> would be kept
     *         8:50       null             60   -> would be deleted
     *         8:50       null             660  -> would be kept
     *
     * @param null   $ttl        optional age in seconds
     * @param string $objectType the type of object
     * @param null   $userIdent  optional user identifier
     * @return bool indicates locks were relinquished
     */
    public function relinquishExpiredLocks($ttl = null, $objectKey = null, $userIdent = null);

    /**
     * Gets an array with the lock info when a valid lock exists.
     *
     * @param string $objectType the type of object
     * @param string $objectKey  the primary key of the object
     * @return array|false lock info or false when not found
     * @throws Exception\RuntimeException
     * @todo Create dedicated value object as response
     */
    public function getLockInfo($objectType, $objectKey);
}