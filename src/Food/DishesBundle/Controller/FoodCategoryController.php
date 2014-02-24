<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

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

    public function dishListAction($categoryId, $listType)
    {
        return $this->render(
            'FoodDishesBundle:FoodCategory:dish_list.html.twig',
            array(
                'dishes' => $this->get('food.dishes')->getActiveDishesByCategory($categoryId),
                'listType' => $listType
            )
        );
    }

    public function restaurantMenuLayoutAction($layout)
    {
        if (!in_array($layout, array('thumbs', 'list'))) {
            $layout = 'thumbs';
        }

        $cookie = new Cookie('restaurant_menu_layout', $layout, (time() + 3600 * 24 * 7 * 30), '/');

        $response = new Response();
        $response->headers->setCookie($cookie);
        return $response->send();
    }
}
