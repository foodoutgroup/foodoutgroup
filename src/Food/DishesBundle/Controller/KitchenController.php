<?php

namespace Food\DishesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class KitchenController extends Controller
{

    public function kitchenlistAction()
    {
        $list = $this->getKitchens();
        return $this->render('FoodDishesBundle:Kitchen:list_items.html.twig', array('list' => $list));
    }


    public function kitchenListWithImagesAction()
    {
        $list = $this->getKitchens();
        return $this->render('FoodDishesBundle:Kitchen:list_items_with_images.html.twig', array('list' => $list));
    }

    /**
     * @return array|\Food\DishesBundle\Entity\Kitchen[]
     */
    private function getKitchens()
    {
        $repo = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen');
        $kitchens = $repo->findBy(
            array('visible' => 1)
        );
        return $kitchens;
    }
}
