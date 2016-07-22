<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

/**
 * Dish units localized
 *
 * @ORM\Table(name="dish_units_localized",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *         "locale", "object_id", "field"
 *     })})
 * @ORM\Entity
 */
class DishUnitsLocalized extends AbstractPersonalTranslation
{
    /**
     * @ORM\ManyToOne(targetEntity="DishUnit", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $object;

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string $locale
     *
     * @ORM\Column(type="string", length=8)
     */
    protected $locale;

    /**
     * @var string $field
     *
     * @ORM\Column(type="string", length=32)
     */
    protected $field;

    /**
     * @var string $content
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $content;

    /**
     * Convenient constructor
     *
     * @param string $locale
     * @param string $field
     * @param string $value
     */
    public function __construct($locale=null, $field=null, $value=null)
    {
        $this->setLocale($locale);
        $this->setField($field);
        $this->setContent($value);
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
     * Set locale
     *
     * @param string $locale
     * @return DishUnitsLocalized
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set field
     *
     * @param string $field
     * @return DishUnitsLocalized
     */
    public function setField($field)
    {
        $this->field = $field;
    
        return $this;
    }

    /**
     * Get field
     *
     * @return string 
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return DishUnitsLocalized
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set object
     *
     * @param $object
     * @return DishUnitsLocalized
     */
    public function setObject($object)
    {
        $this->object = $object;
    
        return $this;
    }

    /**
     * Get object
     *
     * @return \Food\DishesBundle\Entity\DishUnit 
     */
    public function getObject()
    {
        return $this->object;
    }
}