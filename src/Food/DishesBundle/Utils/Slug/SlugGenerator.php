<?php

namespace Fish\ParadoBundle\Utils\Slug;

use Fish\ParadoBundle\Utils\Slug\AbstractStrategy;

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
