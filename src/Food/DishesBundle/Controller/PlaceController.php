<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PlaceController extends Controller
{
    public function indexAction($id, $slug, $categoryId)
    {
        $request = $this->getRequest();
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        $categoryList = $this->get('food.places')->getActiveCategories($place);
        $placePoints = $this->get('food.places')->getPublicPoints($place);
        $categoryRepo = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory');

        $listType = 'thumbs';
        $cookies = $request->cookies;

        if ($cookies->has('restaurant_menu_layout')) {
            $listType = $cookies->get('restaurant_menu_layout');
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
                'placePoints' => $placePoints,
                'listType' => $listType,
            )
        );
    }

    public function filtersListAction()
    {
        return $this->render('FoodDishesBundle:Place:filter_list.html.twig');
    }

    public function placePointAction($point_id)
    {
        $placeService = $this->get('food.places');

        $placePointData = array();
        $placePoint = $placeService->getPlacePointData($point_id);
        if ($placePoint->getActive() && $placePoint->getPublic()) {
            $placePointData = $placePoint->__toArray();
        }

        $response = new JsonResponse($placePointData);
        $response->setCharset('UTF-8');

        $response->prepare($this->getRequest());
        return $response;
    }
}
