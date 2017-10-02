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
        $languageUtil = $this->container->get('food.app.utils.language');

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

        $slugFilter = $request->get('slug_filter');

        $list = $this->getKitchens($request);


        if (empty($selectedKitchens) && !empty($slugFilter)) {
            $slugKitchens = explode('/', $slugFilter);
            $selectedKitchens = $this->formatKitchens($slugKitchens, $list);
        }

        if(empty($selectedKitchensSlugs) && !empty($slugFilter)) {
            $tmp = [];

            foreach ($slugKitchens as $filter) {
                $tmp[] = $languageUtil->removeChars($this->container->getParameter('locale'), $filter);;
            }
            array_unshift($tmp, '');
            $selectedKitchensSlugs = $tmp;
        }

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

    private function formatKitchens($slugs, $kitchens)
    {
        $languageUtil = $this->container->get('food.app.utils.language');
        $return = [];
        if ($kitchens && $slugs) {
            foreach ($kitchens as $kitchen) {
                $value = $languageUtil->removeChars($this->container->getParameter('locale'), $kitchen['name']);
                if (in_array($value, $slugs)) {
                    $return[$value] = $kitchen['id'];
                }
            }
        }

        return $return;
    }

}
