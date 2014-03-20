<?php

namespace Food\DishesBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;

class DishAdminController extends Controller
{
    public function getCategoryOptionsFromPlaceAction($placeId)
    {
        $html = ""; // HTML as response
        $place = $this->getDoctrine()
            ->getRepository('FoodDishesBundle:Place')
            ->find($placeId);

        $categories = $place->getCategories();

        foreach($categories as $category){
            if ($category->getActive()) {
                $html .= '<option value="'.$category->getId().'" >'.$category->getName().'</option>';
            }
        }

        return new Response($html, 200);
    }
}