<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlaceController extends Controller
{
    public function indexAction($id, $slug, $categoryId, $categorySlug)
    {
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        $categoryList = $this->get('food.places')->getActiveCategories($place);
        $categoryRepo = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory');

        if (!empty($categoryId)) {
            $activeCategory = $categoryRepo->find($categoryId);
        } else {
            $activeCategory = $categoryList[0];
        }

        // Jei neparinktas atvaizdavimo tipas - TODO - pick default
        // Pagauk perjungima vaizdo :) TODO
        return $this->render(
            'FoodDishesBundle:Place:index.html.twig',
            array(
                'place' => $place,
                'placeCategories' => $categoryList,
                'selectedCategory' => $activeCategory,
                'listType' => 'list',
            )
        );
    }

    public function filtersListAction()
    {
        return $this->render('FoodDishesBundle:Place:filter_list.html.twig');
    }
}
