<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Common\Restaurant;
use Food\ApiBundle\Common\MenuItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestaurantsController extends Controller
{
    public function getRestaurantsAction(Request $request)
    {
        /**
         * address,city,lat,lng,cuisines,keyword,offset,limit
         *
         */


        $address = $request->get('address');
        $city = $request->get('city');
        $lat = $request->get('lat');
        $lng = $request->get('lng');

        $kitchens = explode(", ", $request->get('cuisines', ''));
        if (empty($kitchens) || (sizeof($kitchens) == 1 && empty($kitchens[0]))) {
            $kitchens = array();
        }
        if (!empty($address)) {

            $location = $this->get('food.googlegis')->getPlaceData($address.', '.$city);
            $locationInfo = $this->get('food.googlegis')->groupData($location, $address, $city);

            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                $kitchens,
                array(),
                false,
                $this->get('food.googlegis')->getLocationFromSession()
            );
        } elseif (!empty($lat) && !empty($lng)) {
            $this->get('food.googlegis')->setLocationToSession(
                array(
                    'lat' => $lat,
                    'lng' => $lng
                )
            );
            $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->magicFindByKitchensIds(
                $kitchens,
                array(),
                false,
                $this->get('food.googlegis')->getLocationFromSession()
            );
        } else {
            $places = array();
        }

        $response = array(
            'restaurants' => array(),
            '_meta' => array(
                'total' => sizeof($places),
                'offset' => 0,
                'limit' => 50
            )
        );
        foreach ($places as $place) {
            $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place['place'], $place['point']);
            $response['restaurants'][] = $restaurant->data;
        }

        return new JsonResponse($response);
    }

    /**
     * @return JsonResponse
     *
     * @todo Countingas pagal objektus kurie netoli yra :D
     */
    public function getRestaurantsFilteredAction()
    {
        $cuisines = array();

        $repository = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen');
        $qb = $repository->createQueryBuilder('k');

        $query = $qb
            ->leftJoin('k.places', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'p.active = 1')
            ->addSelect('k.id, k.name, COUNT(p.id) AS placeCount')
            ->where('k.visible = 1')
            ->groupBy('k.id')
            ->orderBy('placeCount', 'DESC')
            ->having('placeCount > 0')
            ->getQuery();

        $allKitchens = $query->getResult();
        foreach ($allKitchens as $kitchenRow) {
            $cuisines[] = array(
                'id' => $kitchenRow['id'],
                'name' => $kitchenRow['name'],
                'count' => $kitchenRow['placeCount']
            );
        }

        return new JsonResponse($cuisines);
    }

    public function getRestaurantAction($id)
    {
        $place = $this->get('doctrine')->getRepository('FoodDishesBundle:Place')->find(intval($id));
        $restaurant = $this->get('food_api.api')->createRestaurantFromPlace($place, null);
        return new JsonResponse($restaurant->data);
    }

    public function getMenuAction($id)
    {
        $menuItems = $this->get('food_api.api')->createMenuByPlaceId($id);

        $response = array(
            'menu' => $menuItems,
            '_meta' => array(
                'total' => sizeof($menuItems),
                'offset' => 0,
                'limit' => 50
            )
        );
        return new JsonResponse($response);
    }

    public function getMenuItemAction($placeId, $menuItem)
    {
        $response = $this->get('food_api.api')->createMenuItemByPlaceIdAndItemId($placeId, $menuItem);
        return new JsonResponse($response);
    }

    public function getMenuCategoriesAction($id)
    {
        $response = array();
        $items = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory')->findBy(
            array(
                'place' => (int)$id,
                'active' => 1
            ),
            array('lineup'=> 'DESC')
        );
        foreach ($items as $key=>$item) {
            $response[] = array(
                'id' => $item->getId(),
                'name' => $item->getName(),
                'precedence' => ($key+1)
            );
        }
        return new JsonResponse($response);
    }
}
