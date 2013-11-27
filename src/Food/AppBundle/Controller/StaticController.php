<?php

namespace Food\AppBundle\Controller;

class StaticController
{
    public function indexAction($id)
    {

//        $this->

        return $this->render(
            'FoodAppBundle:Static:index.html.twig', array()
//            array('name' => $name)
        );
    }
}