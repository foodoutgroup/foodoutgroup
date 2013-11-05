<?php

namespace Food\OrderBundle\Entity;

use Symfony\Bridge\Doctrine;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\ORM\EntityManager;

/**
 * @ORM\Table(name="order_status_localized")
 * @ORM\Entity
 */
class OrderStatusLocalized
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var integer
     * @ORM\Column(name="lang", type="integer")
     */
    private $lang;

    /**
     * @ORM\ManyToOne(targetEntity="OrderStatus", inversedBy="order_status_id")
     * @ORM\JoinColumn(name="order_status_id", referencedColumnName="id")
     **/
    private $order_status_id;

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
     * Set name
     *
     * @param string $name
     * @return OrderStatusLocalized
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
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

    /**
     * Set lang
     *
     * @param integer $lang
     * @return OrderStatusLocalized
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    
        return $this;
    }

    /**
     * Get lang
     *
     * @return integer 
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set order_status_id
     *
     * @param \Food\OrderBundle\Entity\OrderStatus $orderStatusId
     * @return OrderStatusLocalized
     */
    public function setOrderStatusId(\Food\OrderBundle\Entity\OrderStatus $orderStatusId = null)
    {
        $this->order_status_id = $orderStatusId;
    
        return $this;
    }

    /**
     * Get order_status_id
     *
     * @return \Food\OrderBundle\Entity\OrderStatus 
     */
    public function getOrderStatusId()
    {
        return $this->order_status_id;
    }
}