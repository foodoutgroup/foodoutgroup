<?php
namespace Food\AppBundle\Service;




class HookApplication {
    
    public function build()
    {
        return ['template' => '@FoodApp/Hook/application.html.twig'];
    }
}
