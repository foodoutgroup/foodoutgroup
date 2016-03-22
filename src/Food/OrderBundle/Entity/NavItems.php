<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="nav_items")
 * @ORM\Entity
 */
class NavItems {

    /**
     * @var string
     * @ORM\Column(name="no", type="string", length=20)
     * @ORM\Id
     */
    private $no;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", length=30, nullable=true)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="description2", type="string", length=30, nullable=true)
     */
    private $description2;

    /**
     * Set no
     *
     * @param string $no
     * @return NavItems
     */
    public function setNo($no)
    {
        $this->no = $no;
    
        return $this;
    }

    /**
     * Get no
     *
     * @return string 
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return NavItems
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description2
     *
     * @param string $description2
     * @return NavItems
     */
    public function setDescription2($description2)
    {
        $this->description2 = $description2;
    
        return $this;
    }

    /**
     * Get description2
     *
     * @return string 
     */
    public function getDescription2()
    {
        return $this->description2;
    }
}
