<?php

namespace Food\DishesBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Sonata\DoctrineORMAdminBundle\Tests\Filter\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class KitchenController extends Controller
{

    public function indexAction($id, $slug)
    {
        return $this->render('FoodDishesBundle:Kitchen:index.html.twig', ['kitchen' => $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen')->find($id)]);
    }

    /**
     * Rodomas restoranu sarase
     *
     * @param bool $rush_hour
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction($rush_hour = false, Request $request)
    {

        if ($deliveryType = $request->get('delivery_type', false)) {

            switch ($deliveryType) {
                case 'delivery':
                    $setDeliveryType = OrderService::$deliveryDeliver;
                    break;
                default:
                    $setDeliveryType = $deliveryType;
                    break;
            }
            $this->container->get('session')->set('delivery_type', $setDeliveryType);
        }

        if ($rush_hour) {
            $this->container->get('session')->set('delivery_type', '');
        }

        $selectedKitchens = $request->get('selected_kitchens', '');
        if (!empty($selectedKitchens)) {
            $selectedKitchens = explode(',', $selectedKitchens);
        } else {
            $selectedKitchens = [];
        }

        $selectedKitchensSlugs = $request->get('selected_kitchens_slugs', '');
        if (!empty($selectedKitchensSlugs)) {
            $selectedKitchensSlugs = explode(',', $selectedKitchensSlugs);
        } else {
            $selectedKitchensSlugs = [];
        }

        $list = $this->getKitchens($request);

        return $this->render('FoodDishesBundle:Kitchen:list_items.html.twig', [
                'list' => $list,
                'selected_kitchens' => $selectedKitchens,
                'selected_kitchens_slugs' => $selectedKitchensSlugs,
            ]
        );
    }

    /**
     * @param Request $request
     * @return array|\Food\DishesBundle\Entity\Kitchen[]
     */
    private function getKitchens(Request $request)
    {
        $returnList = array();
        $placeCountArr = array();
        $list = $this->get('food.places')->getPlacesForList(false, $request);

        foreach ($list as $placeRow) {
            foreach ($placeRow['place']->getKitchens() as $kitchen) {
                $kitchen_id = $kitchen->getId();
                $returnList[$kitchen_id]['id'] = $kitchen_id;
                $returnList[$kitchen_id]['name'] = $kitchen->getName();
                if (!isset($returnList[$kitchen_id]['placeCount'])) {
                    $returnList[$kitchen_id]['placeCount'] = 1;
                } else {
                    $returnList[$kitchen_id]['placeCount']++;
                }
                $placeCountArr[$kitchen_id] = $returnList[$kitchen_id]['placeCount'];
            }
        }

        array_multisort($placeCountArr, SORT_DESC, $returnList);

        return $returnList;
    }
}
