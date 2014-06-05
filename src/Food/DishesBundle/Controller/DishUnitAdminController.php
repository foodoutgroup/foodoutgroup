<?php

namespace Food\DishesBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;

class DishUnitAdminController extends Controller
{
    public function getUnitCategoryOptionsFromPlaceAction($placeId)
    {
        $html = ""; // HTML as response
        $categories = $this->getDoctrine()
            ->getRepository('FoodDishesBundle:DishUnitCategory')
            ->findBy(array('place' => $placeId));

        foreach($categories as $category){
            $html .= '<option value="'.$category->getId().'" >'.$category->getName().'</option>';
        }

        return new Response($html, 200);
    }
}