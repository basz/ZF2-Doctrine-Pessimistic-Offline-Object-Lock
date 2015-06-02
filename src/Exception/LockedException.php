<?php

namespace HF\POOL\Exception;

class LockedException extends \RuntimeException
{

    /**
     *
     */
    const MESSAGE_LOCKED = "Object (%s:%s) is locked";

    /**
     * @var array
     */
    public $lockInfo;

    /**
     * @param array $lockInfo Information about the lock
     */
    public function __construct($lockInfo)
    {
        $this->lockInfo = $lockInfo;

        $message = sprintf(self::MESSAGE_LOCKED, $lockInfo['objectType'], $lockInfo['objectKey']);

        parent::__construct($message);
    }

}