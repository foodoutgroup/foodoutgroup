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

    public function getDishOptionsFromPlaceAction($placeId, $uniqueId)
    {
        $html = ""; // HTML as response
        $options = $this->getDoctrine()
            ->getRepository('FoodDishesBundle:DishOption')
            ->findBy(array('place' => $placeId));

        $elementNo = 0;

        foreach($options as $option){
            if (!$option->getHidden()) {
                $elementNo++;

                $html .=
                    '<li style="width: 220px; float: left; display: block">'
                    .'  <label>'.
                    '       <input type="checkbox" id="'.$uniqueId.'_options_'.$elementNo.'" name="'.$uniqueId.'[options][]" value="'.$option->getId().'">'
                    .'      <span>'.$option->getName().'</span>'
                    .'  </label>'
                    .'</li>';
            }
        }

        return new Response($html, 200);
    }
}