<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_data_import")
 * @ORM\Entity
 */
class OrderDataImport
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
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="id")
     **/
    private $user;

    /**
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;
    
    private $file;

    /**
     * @ORM\Column(name="infodata", type="text")
     */
    private $infodata;

    /**
     * @ORM\ManyToMany(targetEntity="Food\OrderBundle\Entity\Order", inversedBy="orders")
     */
    private $ordersChanged;

    public function __construct()
    {
        $this->ordersChanged = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set date
     *
     * @param \DateTime $date
     * @return OrderDataImport
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return OrderDataImport
     */
    public function setUser(\Food\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Food\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getInfodata()
    {
        return $this->infodata;
    }

    /**
     * @param mixed $infodata
     */
    public function setInfodata($infodata)
    {
        $this->infodata = $infodata;
    }

    /**
     * @return mixed
     */
    public function getOrdersChanged()
    {
        return $this->ordersChanged;
    }
}