<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\AppBundle\Entity\Uploadable;

/**
 * BannerLinks
 *
 * @ORM\Table(name="banner_links")
 * @ORM\Entity(repositoryClass="Food\DishesBundle\Entity\BannerLinksRepository")
 */
class BannerLinks extends Uploadable
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
     * @ORM\Column(name="url_from", type="string", length=255)
     */
    private $urlFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="url_to", type="string", length=255)
     */
    private $urlTo;

    /**
     * @var string
     *
     * @ORM\Column(name="element", type="string", length=255)
     */
    private $element;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=255)
     */
    private $text;


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
     * Set urlFrom
     *
     * @param string $urlFrom
     * @return BannerLinks
     */
    public function setUrlFrom($urlFrom)
    {
        $this->urlFrom = $urlFrom;

        return $this;
    }

    /**
     * Get urlFrom
     *
     * @return string 
     */
    public function getUrlFrom()
    {
        return $this->urlFrom;
    }

    /**
     * Set urlTo
     *
     * @param string $urlTo
     * @return BannerLinks
     */
    public function setUrlTo($urlTo)
    {
        $this->urlTo = $urlTo;

        return $this;
    }

    /**
     * Get urlTo
     *
     * @return string 
     */
    public function getUrlTo()
    {
        return $this->urlTo;
    }

    /**
     * Set element
     *
     * @param string $element
     * @return BannerLinks
     */
    public function setElement($element)
    {
        $this->element = $element;

        return $this;
    }

    /**
     * Get element
     *
     * @return string 
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return BannerLinks
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }
}
