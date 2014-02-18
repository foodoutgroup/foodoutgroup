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

    public function dishListAction($categoryId)
    {
        return $this->render(
            'FoodDishesBundle:FoodCategory:dish_list.html.twig',
            array(
                'dishes' => $this->get('food.dishes')->getActiveDishesByCategory($categoryId),
            )
        );
    }

    public function dishTableAction($categoryId)
    {
        return $this->render(
            'FoodDishesBundle:FoodCategory:dish_table.html.twig',
            array(
                'dishes' => $this->get('food.dishes')->getActiveDishesByCategory($categoryId),
            )
        );
    }
}
