<?php

namespace Food\DishesBundle\Controller;

use Sonata\DoctrineORMAdminBundle\Tests\Filter\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class KitchenController extends Controller
{

    public function indexAction($id, $slug)
    {
        $kitchen = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen')->find($id);

        return $this->render('FoodDishesBundle:Kitchen:index.html.twig', array('kitchen' => $kitchen));
    }

    /**
     * Rodomas restoranu sarase
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function kitchenlistAction($recommended = false, Request $request)
    {
        if ($recommended) {
            $recommended = true;
        }

        $list = $this->getKitchens($recommended, $request);

        return $this->render('FoodDishesBundle:Kitchen:list_items.html.twig', array('list' => $list));
    }

    /**
     * @todo - patikrinti reikalinguma. Nebeliko ikonkiu prie virtuviu
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function kitchenListWithImagesAction()
    {
        $list = $this->getKitchens();

        return $this->render('FoodDishesBundle:Kitchen:list_items_with_images.html.twig', array('list' => $list));
    }

    /**
     * @return array|\Food\DishesBundle\Entity\Kitchen[]
     */
    private function getKitchens($recommended, Request $request)
    {
        $returnList = array();
        $placeCountArr = array();
        $list = $this->get('food.places')->getPlacesForList($recommended, $request);

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