<?php

namespace Food\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\ReportBundle\Entity\RfmStatus
 *
 * @ORM\Entity(repositoryClass="RfmStatusRepository")
 * @ORM\Table(name="rfm_status")
 */
class RfmStatus
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var float
     * @ORM\Column(name="rfm_from", type="integer", nullable=true)
     */
    private $from;

    /**
     * @var float
     * @ORM\Column(name="rfm_to", type="integer", nullable=true)
     */
    private $to;

    /**
     * @var float
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    private $title;

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
     * Set from
     *
     * @param integer $from
     * @return RfmStatus
     */
    public function setFrom($from)
    {
        $this->from = $from;
    
        return $this;
    }

    /**
     * Get from
     *
     * @return integer 
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param integer $to
     * @return RfmStatus
     */
    public function setTo($to)
    {
        $this->to = $to;
    
        return $this;
    }

    /**
     * Get to
     *
     * @return integer 
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return RfmStatus
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }
}