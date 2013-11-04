<?php

namespace Food\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FoodUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
