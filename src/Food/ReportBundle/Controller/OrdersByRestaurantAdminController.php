<?php

namespace Food\ReportBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;

class OrdersByRestaurantAdminController extends Controller
{
    public function listAction()
    {
        $request = $this->get('request');

        return $this->render('FoodReportBundle:Report/OrdersByRestaurant:list.html.twig', [

        ]);
    }

    public function generateAction()
    {
        return $this->renderJson([]);
    }
}
