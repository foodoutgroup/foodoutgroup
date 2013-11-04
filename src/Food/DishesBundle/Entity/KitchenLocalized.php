<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KitchenLocalized
 *
 * @ORM\Table(name="kitchen_localized")
 * @ORM\Entity
 */
class KitchenLocalized
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
     *
     * @ORM\Column(name="lang", type="integer")
     */
    private $lang;


    /**
     * @ORM\ManyToOne(targetEntity="Kitchen", inversedBy="kitchen")
     * @ORM\JoinColumn(name="kitchen_id", referencedColumnName="id")
     **/
    private $kitchen;

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
     * @return KitchenLocalized
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
     * @return KitchenLocalized
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
     * Set kitchen
     *
     * @param \Food\DishesBundle\Entity\Kitchen $kitchen
     * @return KitchenLocalized
     */
    public function setKitchen(\Food\DishesBundle\Entity\Kitchen $kitchen = null)
    {
        $this->kitchen = $kitchen;
    
        return $this;
    }

    /**
     * Get kitchen
     *
     * @return \Food\DishesBundle\Entity\Kitchen 
     */
    public function getKitchen()
    {
        return $this->kitchen;
    }
}