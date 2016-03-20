<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_mail_log")
 * @ORM\Entity
 */
class OrderMailLog
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
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="orderMailLog")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;

    /**
     * @var \DateTime
     * @ORM\Column(name="event_date", type="datetime")
     */
    private $event_date;

    /**
     * @var string
     * @ORM\Column(name="source", type="string", length=50, nullable=true)
     */
    private $source;

    /**
     * @var string
     * @ORM\Column(name="template", type="string", length=100, nullable=true)
     */
    private $template;

    /**
     * @var string
     * @ORM\Column(name="params", type="text", nullable=true)
     */
    private $params;

    public function __construct()
    {
        $this->event_date = new \DateTime("now");
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
     * Set event_date
     *
     * @param \DateTime $eventDate
     * @return OrderMailLog
     */
    public function setEventDate($eventDate)
    {
        $this->event_date = $eventDate;
    
        return $this;
    }

    /**
     * Get event_date
     *
     * @return \DateTime 
     */
    public function getEventDate()
    {
        return $this->event_date;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return OrderMailLog
     */
    public function setSource($source)
    {
        $this->source = $source;
    
        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return OrderMailLog
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    
        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set params
     *
     * @param string $params
     * @return OrderMailLog
     */
    public function setParams($params)
    {
        $this->params = $params;
    
        return $this;
    }

    /**
     * Get params
     *
     * @return string 
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set order
     *
     * @param \Food\OrderBundle\Entity\Order $order
     * @return OrderMailLog
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
}
