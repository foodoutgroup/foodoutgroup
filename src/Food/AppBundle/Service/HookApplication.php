<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class HookApplication {
    
    public function build()
    {
        return ['template' => '@FoodApp/Hook/application.html.twig'];
    }
}
