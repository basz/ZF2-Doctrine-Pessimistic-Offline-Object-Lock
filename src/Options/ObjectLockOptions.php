<?php

namespace HF\POOL\Options;

use Zend\Stdlib\AbstractOptions;

class ObjectLockOptions extends AbstractOptions
{
    /**
     * Time to life in seconds
     *
     * @var int
     */
    protected $ttl = 900;

    /**
     * Doctrine connection to use
     *
     * @var string
     */
    protected $doctrineConnection = 'doctrine.connection.orm_default';

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * @return string
     */
    public function getDoctrineConnection()
    {
        return $this->doctrineConnection;
    }

    /**
     * @param string $doctrineConnection
     */
    public function setDoctrineConnection($doctrineConnection)
    {
        $this->doctrineConnection = $doctrineConnection;
    }

}