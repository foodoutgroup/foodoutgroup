<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
