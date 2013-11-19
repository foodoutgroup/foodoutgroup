<?php

namespace Food\DishesBundle\Utils\Slug;

use Food\DishesBundle\Utils\Slug\AbstractStrategy;

class SlugGenerator
{
    private $strategy;

    public function __construct(AbstractStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function generate($langId)
    {
        $this->strategy->generate($langId);
    }
}
