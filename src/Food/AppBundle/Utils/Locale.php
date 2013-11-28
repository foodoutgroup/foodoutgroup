<?php

namespace Food\AppBundle\Utils;


use Food\DishesBundle\Utils\Slug\FoodCategoryStrategy;
use Food\DishesBundle\Utils\Slug\SlugGenerator;
use Food\DishesBundle\Utils\Slug\TextStrategy;
use Food\AppBundle\Entity;
use Food\AppBundle\Entity\Slug as SlugEntity;
use Food\AppBundle\Traits;

class Locale
{
    private $locale;

    public function __construct($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getLocale()
    {
        return $this->locale;
    }

}
?>