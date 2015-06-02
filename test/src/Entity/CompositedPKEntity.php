<?php

namespace HFTest\POOL\Entity;

/**
 * CompositedPKEntity
 */
class CompositedPKEntity
{
    /**
     * @var integer
     */
    private $first_pk;

    /**
     * @var integer
     */
    private $second_pk;

    /**
     * @var string
     */
    private $name;

    /**
     * @param (integer) $firstPk
     * @param (integer) $secondPk
     */
    public function __construct($firstPk, $secondPk)
    {
        $this->first_pk = $firstPk;
        $this->second_pk = $secondPk;
    }

    /**
     * Get firstPk
     *
     * @return integer
     */
    public function getFirstPk()
    {
        return $this->first_pk;
    }

    /**
     * Get secondPk
     *
     * @return integer
     */
    public function getSecondPk()
    {
        return $this->second_pk;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

