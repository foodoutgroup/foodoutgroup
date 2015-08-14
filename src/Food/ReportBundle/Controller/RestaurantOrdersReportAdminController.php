<?php

namespace Food\ReportBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;

class RestaurantOrdersReportAdminController extends Controller
{
    public function listAction()
    {
        @ini_set("max_execution_time", 0);

        $request = $this->get('request');
        $orderRepo = $this->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $place_filter = array();
        $placeRepo = $this->get('doctrine')->getRepository('FoodDishesBundle:Place');
        if ($this->isModerator()) {
            $place_filter = array('id' => $this->getUser()->getPlace()->getId());
        }

        $dateFrom = new \DateTime($request->get('date_from', '-1 month'));
        $dateTo = new \DateTime($request->get('date_to', 'now'));

        $places = $request->get('place', $place_filter);
        $groupMonth = $request->get('group_month', false);

        $stats = $orderRepo->getPlacesOrdersForRange($dateFrom, $dateTo, $places, $groupMonth);

        $orders = array();
        if (!empty($places)) {
            foreach ($stats as $row) {
                $orders[$row['id']]['id'] = $row['id'];
                $orders[$row['id']]['place_name'] = $row['place_name'];
                $orders[$row['id']]['order_date'] = $row['order_date'];
                if ($groupMonth) {
                    $orders[$row['id']]['month'] = $row['month'];
                }
                $orders[$row['id']]['total'] = $row['total'];
                $orders[$row['id']]['place_point_address'] = $row['place_point_address'];
                $orders[$row['id']]['order_status'] = $row['order_status'];
                $orders[$row['id']]['payment_status'] = $row['payment_status'];
                $orders[$row['id']]['delivery_type'] = $row['delivery_type'];
                $orders[$row['id']]['accept_time'] = $row['accept_time'];
                $orders[$row['id']]['delivery_pickup_time'] = $row['delivery_pickup_time'];
                $orders[$row['id']]['delivery_time'] = $row['delivery_time'];

                $orders[$row['id']]['details'] = array();
                $orders_detail = $orderRepo->getOrderDetails($row['id']);
                if (!empty($orders_detail) && count($orders_detail) > 0) {
                    foreach ($orders_detail as $detail) {
                        $orders[$row['id']]['details'][$detail['id']]['dish_name'] = $detail['dish_name'];
                        $orders[$row['id']]['details'][$detail['id']]['price'] = $detail['price'];
                        $orders[$row['id']]['details'][$detail['id']]['quantity'] = $detail['quantity'];
                        $orders[$row['id']]['details'][$detail['id']]['dish_unit_name'] = $detail['dish_unit_name'];
                        $orders[$row['id']]['details'][$detail['id']]['dish_size_code'] = $detail['dish_size_code'];

                        $order = $orderRepo->findOneBy(array('id' => $detail['order_id']));
                        foreach ($order->getDetails() as $ord) {
                            $orders[$row['id']]['details'][$detail['id']]['options'] = $ord->getOptions();
                        }
                    }
                }
            }
        }

        return $this->render(
            'FoodReportBundle:Report:restaurant_order_report.html.twig',
            array(
                'stats' => $orders,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'user' => $this->getUser(),
                'placesSelected' => $places,
                'places' => $placeRepo->findBy(
                    array('active' => 1),
                    array('name' => 'ASC')
                ),
                'groupMonth' => $groupMonth,
                'getOrderDetailByID' => $this->getOrderDetailByID(),
                'isAdmin' => $this->isAdmin(),
            )
        );
    }

    public function getOrderDetailByID() {
        $request = $this->get('request');
        $order_id = $request->get('order_id', false);

        $orderRepo = $this->get('doctrine')->getRepository('FoodOrderBundle:Order');
        return $orderRepo->getOrderDetails($order_id);
    }

    /**
     * Is user just a place moderator?
     *
     * @return bool
     */
    public function isModerator()
    {
        $securityContext = $this->get('security.context');
        return (
            !$securityContext->isGranted('ROLE_ADMIN')
            && $securityContext->isGranted('ROLE_MODERATOR')
        );
    }

    /**
     * Is user as powerfull as Terminator? Is he Mister Administrator?
     *
     * @return bool
     */
    public function isAdmin()
    {
        $securityContext = $this->get('security.context');
        return  $securityContext->isGranted('ROLE_ADMIN');
    }
}
