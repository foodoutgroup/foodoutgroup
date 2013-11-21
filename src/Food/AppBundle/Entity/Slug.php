<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\Slug
 *
 * @ORM\Table(name="common_slug", indexes={@ORM\Index(name="item_id_idx", columns={"item_id"}), @ORM\Index(name="item_id_type_idx", columns={"item_id", "type"})}, uniqueConstraints={@ORM\UniqueConstraint(name="type_name_unq", columns={"type", "name"})})
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\SlugRepository")
 */
class Slug
{
    const TYPE_CATEGORY = 'category';
    const TYPE_DISH = 'dish';
    const TYPE_PLACE = 'place';
    const TYPE_TEXT = 'text';


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
     * @var integer $lang_id
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
     * @var integer $is_active
     *
     * @ORM\Column(name="is_active", type="smallint")
     */
    private $is_active = 1;


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
     * @param integer $langId
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
     * @return integer
     */
    public function getLangId()
    {
        return $this->lang_id;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Slug
     */
    public function setType($type)
    {
        $allowedTypes = [
            self::TYPE_CATEGORY,
            self::TYPE_DISH,
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
     * @param integer $isActive
     * @return Slug
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;

        return $this;
    }

    /**
     * Get is_active
     *
     * @return integer
     */
    public function getIsActive()
    {
        return $this->is_active;
    }
}