<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\Param
 *
 * @ORM\Table(name="params")
 * @ORM\Entity
 */
class Param
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="param", type="string", length=64, unique=true)
     */
    private $param;

    /**
     * @var string
     * @ORM\Column(name="value", type="string", length=254)
     */
    private $value;

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getId().'-'.$this->getParam();
        }

        return '';
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set param
     *
     * @param string $param
     * @return Params
     */
    public function setParam($param)
    {
        $this->param = $param;
    
        return $this;
    }

    /**
     * Get param
     *
     * @return string 
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Params
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }
}