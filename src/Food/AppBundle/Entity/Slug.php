<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\Slug
 *
 * @ORM\Table(name="common_slug", indexes={@ORM\Index(name="item_id_idx", columns={"item_id"}), @ORM\Index(name="item_id_type_idx", columns={"item_id", "type", "lang_id"})}, uniqueConstraints={@ORM\UniqueConstraint(name="type_name_unq", columns={"type", "name", "lang_id"})})
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\SlugRepository")
 */
class Slug
{
    const TYPE_KITCHEN = 'kitchen';
    const TYPE_PLACE = 'place';
    const TYPE_FOOD_CATEGORY = 'food_category';
    const TYPE_TEXT = 'text';
    const TYPE_PAGE = 'page';


    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer $item_id
     *
     * @ORM\Column(name="item_id", type="integer")
     */
    private $item_id;

    /**
     * @var string $lang_id
     *
     * @ORM\Column(name="lang_id", type="string", length=3)
     */
    private $lang_id;

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=16)
     */
    private $type;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string $name
     *
     * @ORM\Column(name="orig_name", type="string", length=255)
     */
    private $orig_name;

    /**
     * @var boolean $active
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $active = true;


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
     * Set item_id
     *
     * @param integer $itemId
     * @return Slug
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get item_id
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set lang_id
     *
     * @param string $langId
     * @return Slug
     */
    public function setLangId($langId)
    {
        $this->lang_id = $langId;

        return $this;
    }

    /**
     * Get lang_id
     *
     * @return string
     */
    public function getLangId()
    {
        return $this->lang_id;
    }

    /**
     * Set type
     *
     * @param string $type
     * @throws \InvalidArgumentException
     * @return Slug
     */
    public function setType($type)
    {
        $allowedTypes = [
            self::TYPE_KITCHEN,
            self::TYPE_FOOD_CATEGORY,
            self::TYPE_PLACE,
            self::TYPE_TEXT
        ];

        if (!in_array($type, $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid $type value.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Slug
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
     * Set is_active
     *
     * @param boolean $isActive
     * @return Slug
     */
    public function setActive($isActive)
    {
        $this->active = $isActive;

        return $this;
    }

    /**
     * Get is_active
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->getActive();
    }

    /**
     * Set orig_name
     *
     * @param string $origName
     * @return Slug
     */
    public function setOrigName($origName)
    {
        $this->orig_name = $origName;
    
        return $this;
    }

    /**
     * Get orig_name
     *
     * @return string 
     */
    public function getOrigName()
    {
        return $this->orig_name;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }
}