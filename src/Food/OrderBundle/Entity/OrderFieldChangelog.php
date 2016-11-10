<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_field_changelog")
 * @ORM\Entity
 */
class OrderFieldChangelog
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
     * @ORM\ManyToOne(targetEntity="Food\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(referencedColumnName="id")
     **/
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="id")
     **/
    private $user;

    /**
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(name="old_value", type="text", nullable=true)
     */
    private $oldValue;

    /**
     * @ORM\Column(name="new_value", type="text", nullable=true)
     */
    private $newValue;

    /**
     * @ORM\ManyToOne(targetEntity="Food\OrderBundle\Entity\OrderDataImport")
     * @ORM\JoinColumn(name="data_import_id", referencedColumnName="id")
     **/
    private $dataImport;

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
     * @return OrderFieldChangelog
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
     * Set oldValue
     *
     * @param string $oldValue
     * @return OrderFieldChangelog
     */
    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;
    
        return $this;
    }

    /**
     * Get oldValue
     *
     * @return string 
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * Set newValue
     *
     * @param string $newValue
     * @return OrderFieldChangelog
     */
    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;
    
        return $this;
    }

    /**
     * Get newValue
     *
     * @return string 
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderFieldChangelog
     */
    public function setOrder(\Food\OrderBundle\Entity\Order $order = null)
    {
        $this->order = $order;
    
        return $this;
    }

    /**
     * Get order
     *
     * @return \Food\OrderBundle\Entity\Order 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return OrderFieldChangelog
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
    public function getDataImport()
    {
        return $this->dataImport;
    }

    /**
     * @param mixed $dataImport
     */
    public function setDataImport($dataImport)
    {
        $this->dataImport = $dataImport;
    }
}