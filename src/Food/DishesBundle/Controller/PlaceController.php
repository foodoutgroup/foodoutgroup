<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlaceController extends Controller
{
    public function indexAction($id, $slug, $categoryId)
    {
        $request = $this->getRequest();
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        $categoryList = $this->get('food.places')->getActiveCategories($place);
        $categoryRepo = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory');

        $listType = 'thumbs';
        // TODO save i sessija, tolesniam atsiminimui :)
        if ($request->getMethod() == 'POST') {
            $listType = $request->get('view-type', 'thumbs');
        }

        if (!empty($categoryId)) {
            $activeCategory = $categoryRepo->find($categoryId);
        } else {
            $activeCategory = $categoryList[0];
        }

        return $this->render(
            'FoodDishesBundle:Place:index.html.twig',
            array(
                'place' => $place,
                'placeCategories' => $categoryList,
                'selectedCategory' => $activeCategory,
                'listType' => $listType,
            )
        );
    }

    public function filtersListAction()
    {
        return $this->render('FoodDishesBundle:Place:filter_list.html.twig');
    }
}
