<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FoodCategoryController extends Controller
{
    public function indexAction($id, $slug)
    {
        $category = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory')->find($id);
        return $this->render(
            'FoodDishesBundle:FoodCategory:index.html.twig',
            array(
                'category' => $category,
                'place' => $category->getPlace()
            )
        );
    }
}
