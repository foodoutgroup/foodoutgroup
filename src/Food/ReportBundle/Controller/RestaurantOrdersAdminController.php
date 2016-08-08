<?php

namespace Food\ReportBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;

class RestaurantOrdersAdminController extends Controller
{
    public function listAction()
    {
        $request = $this->get('request');
        $orderRepo = $this->get('doctrine')->getRepository('FoodOrderBundle:Order');
        $placeRepo = $this->get('doctrine')->getRepository('FoodDishesBundle:Place');

        $dateFrom = new \DateTime($request->get('date_from', '-1 month'));
        $dateTo = new \DateTime($request->get('date_to', 'now'));
        $places = $request->get('place', array());
        $groupMonth = $request->get('group_month', false);
        $companyCode = $request->get('company_code', '');

        $stats = $orderRepo->getPlacesOrderCountForRange($dateFrom, $dateTo, $places, $companyCode, $groupMonth);

        return $this->render(
            'FoodReportBundle:Report:restaurant_order.html.twig',
            array(
                'stats' => $stats,
                'dateFrom' => $dateFrom,
                'companyCode' => $companyCode,
                'dateTo' => $dateTo,
                'placesSelected' => $places,
                'places' => $placeRepo->findBy(
                    array('active' => 1),
                    array('name' => 'ASC')
                ),
                'groupMonth' => $groupMonth,
            )
        );
    }
}
