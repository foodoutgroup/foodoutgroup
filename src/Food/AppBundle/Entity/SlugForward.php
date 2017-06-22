<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Food\AppBundle\Entity\SlugForward
 *
 * @ORM\Table(name="common_slug_forward", uniqueConstraints={@ORM\UniqueConstraint(name="type_unique", columns={"slug", "locale"})})
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\SlugForwardRepository")
 */
class SlugForward
{

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    private $slug;

    /**
     * @var string $locale
     *
     * @ORM\Column(name="locale", type="string", length=3)
     */
    private $locale;

    /**
     * @var string $controller
     *
     * @ORM\Column(name="controller", type="string", length=255)
     */
    private $controller;

    /**
     * @var string $params
     *
     * @ORM\Column(name="params", type="text", nullable=true)
     */
    private $params;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        if($this->params != null) {
            $params = json_decode($this->params, true);
        }

        if(json_last_error() != JSON_ERROR_NONE || $this->params == null) {
            $params = [];
        }

        return $params;
    }

    /**
     * @param string $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }



}