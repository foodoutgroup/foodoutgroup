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

        $html .= '<option value=""></option>';
        foreach($categories as $category){
            if ($category->getActive()) {
                $html .= '<option value="'.$category->getId().'" >'.$category->getName().'</option>';
            }
        }

        return new Response($html, 200);
    }

    public function getDishUnitsFromPlaceAction($placeId)
    {
        $html = ""; // HTML as response
        $place = $this->getDoctrine()
            ->getRepository('FoodDishesBundle:Place')
            ->find($placeId);

        $units = $this->getDoctrine()
                ->getRepository('FoodDishesBundle:DishUnit')
                ->findBy(array('place' => $place));

        foreach($units as $unit){
            $html .= '<option value="'.$unit->getId().'" >'.$unit->getName().'</option>';
        }

        return new Response($html, 200);
    }

    public function getPointsFromPlaceAction($placeId)
    {
        $html = ""; // HTML as response
        $place = $this->getDoctrine()
            ->getRepository('FoodDishesBundle:Place')
            ->find($placeId);


        foreach($place->getPoints() as $point){
            $html .= '<option value="'.$point->getId().'" >'.$point.'</option>';
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
                    '<li style="width: 330px; float: left; display: block">'
                    .'  <label>'.
                    '       <input type="checkbox" id="'.$uniqueId.'_options_'.$elementNo.'" name="'.$uniqueId.'[options][]" value="'.$option->getId().'">'
                    .'      <span title="'.$option->getCode().'">'.$option->getName().($option->getGroupName() ? ' <b>(G:</b> '.$option->getGroupName().'<b>)</b>':'')
                            .($option->getSingleSelect() ? ' <b>Vnt</b>' : '')
                            .'</span>'
                    .'  </label>'
                    .'</li>';
            }
        }

        return new Response($html, 200);
    }
}