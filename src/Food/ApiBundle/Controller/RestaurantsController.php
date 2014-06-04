<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Common\Restaurant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RestaurantsController extends Controller
{
    public function getRestaurantsAction()
    {
        $data = new Restaurant();
        var_dump($data->get('delivery_options', 'price'));
        //echo "<pre>";
        //print_r($data->data);
        //$data->set('delivery_options.price', array('amount'=>'16'));
        //print_r($data->data);
        //echo "</pre>";
        return new Response();
    }

    public function getRestaurantsFilteredAction()
    {

    }

    public function getRestaurantAction($id)
    {

    }
}
