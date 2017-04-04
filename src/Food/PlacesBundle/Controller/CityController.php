<?php

namespace Food\PlacesBundle\Controller;

use Food\AppBundle\Entity\Slug;
use Food\AppBundle\Service\CityService;
use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class CityController extends Controller
{

    public function indexAction($id, $params = [])
    {
        $request = $this->get('request');

        $cityService = $this->get('food.city_service');
        if (!$city = $cityService->getCityById($id)) {
            if (!$city = $cityService->getDefaultCity()) {
                throw new NotFoundHttpException('City was not found');
            }
        }

        $metaTitle = '';
        $metaDescription = '';

        $this->get('food.googlegis')->setCityOnlyToSession($city,$id);
        $locData = $this->get('food.googlegis')->getLocationFromSession();
        $placeService = $this->get('food.places');
        $selectedKitchensNames = $placeService->getKitchensFromSlug($params, $request, true);

        $selectedKitchensIds = $placeService->getKitchensFromSlug($params, $request);

        if (!empty($selectedKitchensIds)) {
            $kitchen = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen')->find($selectedKitchensIds[0]);
            $metaTitle = $kitchen->getMetaTitle();
            $metaDescription = $kitchen->getMetaDescription();
        }


        return $this->render(
            'FoodPlacesBundle:City:index.html.twig',
            array(
                'recommended' => false,
                'rush_hour' => false,
                'location' => $locData,
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
                'slug_filter' => implode("/", $params),
                'selected_kitchens_names' => $selectedKitchensNames,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'city' => $city,
                'current_url' => $request->getUri(),
                'current_url_path' => $this->get('slug')->getUrl($id, Slug::TYPE_CITY),
            )
        );
    }
    /**
     * AJAX
     * @param bool $isRecommended
     * @param bool $slug_filter
     * @param bool $rush_hour
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction($isRecommended = false, $slug_filter = false, $rush_hour = false, Request $request)
    {
        if ($recommendedFromRequest = $request->get('recommended', null) !== null) {
            $isRecommended = (bool)$recommendedFromRequest;
        }

        if ($deliveryType = $request->get('delivery_type', false)) {
            switch ($deliveryType) {
                // @TODO: delivery !== deliver
                case 'delivery':
                    $this->container->get('session')->set('delivery_type', OrderService::$deliveryDeliver);
                    break;
                case 'pickup':
                    $this->container->get('session')->set('delivery_type', OrderService::$deliveryPickup);
                    break;
            }
        }

        $placeCollection = $this->get('food.places')->getPlacesForList($isRecommended, $request, $slug_filter, $rush_hour);

        $locationData = $this->get('food.googlegis')->getLocationFromSession();

        return $this->render(
            'FoodPlacesBundle:City:list.html.twig',
            array(
                'placeCollection' => $placeCollection,
                'isRecommended' => $isRecommended,
                'location' => $locationData,
                'location_show' => !empty($locationData),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver)
            )
        );
    }

    public function changeLocationAction()
    {
        return $this->render('FoodPlacesBundle:Default:change_location.html.twig');
    }
}
