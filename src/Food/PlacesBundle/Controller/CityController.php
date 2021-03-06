<?php

namespace Food\PlacesBundle\Controller;

use Food\AppBundle\Entity\Slug;
use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class CityController extends Controller
{

    public function indexAction($id, $params = [])
    {

        $badge = $this->get('session')->get('badge');

        if($badge){
            $this->container->get('session')->set('delivery_type', OrderService::$deliveryPedestrian);
            $this->get('session')->remove('badge');
        }else{
            $this->container->get('session')->set('delivery_type', OrderService::$deliveryDeliver);
        }


        $request = $this->get('request');

        $cityService = $this->get('food.city_service');
        if (!$city = $cityService->getCityById($id)) {
            if (!$city = $cityService->getDefaultCity()) {
                throw new NotFoundHttpException('City was not found');
            }
        }
        $lService = $this->get('food.location');

        $metaTitle = $city->getMetaTitle();
        $metaDescription = $city->getMetaTitle();

        // TODO: get meta data by kitchen filter :D

        $locationData = $lService->get();
        if($locationData == null || (array_key_exists('city_id', $locationData) && $locationData['city_id'] != $city->getId())) {
            $dataToSet = $lService->findByAddress($city->getTitle().", ".$this->container->getParameter('country_full'));
            $dataToSet['city_id'] = $city->getId();
            $dataToSet['city'] = $city->getTitle();
            $dataToSet['output'] = null;
            $dataToSet['flat'] = null;
            $lService->clear()->set($dataToSet);
        }
        $placeService = $this->get('food.places');

        return $this->render(
            'FoodPlacesBundle:City:index.html.twig', [
                'rush_hour' => in_array('recom564fsa564fsa564fsa564f5s6a4', $params), // todo MULTI-L param for rush_hour list
                'location' => $lService->get(),
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
                'slug_filter' => implode("/", $params),
                'kitchen_collection' => [],
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'city' => $city,
                'current_url' => $request->getUri(),
                'current_url_path' => $this->get('slug')->getUrl($id, Slug::TYPE_CITY),
            ]
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
    public function listAction($slug_filter = false, $rush_hour = false, Request $request)
    {

        if ($deliveryType = $request->get('delivery_type', false)) {
            switch ($deliveryType) {
                // @TODO: delivery !== deliver
                case 'delivery':
                    $this->container->get('session')->set('delivery_type', OrderService::$deliveryDeliver);
                    break;
                case 'pickup':
                    $this->container->get('session')->set('delivery_type', OrderService::$deliveryPickup);
                    break;
                case 'pedestrian':
                    $this->container->get('session')->set('delivery_type', OrderService::$deliveryPedestrian);
                    break;
            }
        }

        $placeCollection = $this->get('food.places')->getPlacesForList($rush_hour, $request, true);

        return $this->render(
            'FoodPlacesBundle:City:list.html.twig',
            array(
                'reviewsEnabled' => $this->get('food.app.utils.misc')->getParam('reviews_enabled', 0),
                'placeCollection' => $placeCollection,
                'location' => $this->get('food.location')->get(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver)
            )
        );
    }
}
