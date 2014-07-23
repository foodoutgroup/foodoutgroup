<?php

namespace Food\OrderBundle\Entity;
use Doctrine\ORM\EntityRepository;
use Food\OrderBundle\Service\OrderService;

class OrderRepository extends EntityRepository
{
    /**
     * @param string $city
     * @return array
     */
    public function getOrdersUnassigned($city)
    {
        $date = new \DateTime();
        $date->modify("-2 minutes");

        $filter = array(
            'order_status' =>  array(
                OrderService::$status_accepted,
                OrderService::$status_delayed,
                OrderService::$status_finished
            ),
            'place_point_city' => $city,
            'deliveryType' => OrderService::$deliveryDeliver,
            'order_date_more' => $date,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string $city
     * @return array
     */
    public function getOrdersUnconfirmed($city)
    {
        $filter = array(
            'order_status' =>  array(OrderService::$status_new),
            'place_point_city' => $city,
            'deliveryType' => OrderService::$deliveryDeliver,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string $city
     * @return array
     */
    public function getOrdersAssigned($city)
    {
        $filter = array(
            'order_status' =>  OrderService::$status_assiged,
            'place_point_city' => $city,
            'deliveryType' => OrderService::$deliveryDeliver,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @var string|null $date
     * @return array
     */
    public function getYesterdayOrdersGrouped($date = null)
    {
        if (empty($date)) {
            $dateFrom = new \DateTime(date("Y-m-d 00:00:00", strtotime('-1 day')));
            $dateTo = new \DateTime(date("Y-m-d 23:59:59", strtotime('-1 day')));
        } else {
            $dateFrom = new \DateTime(date("Y-m-d 00:00:00", strtotime($date)));
            $dateTo = new \DateTime(date("Y-m-d 23:59:59", strtotime($date)));
        }

        $filter = array(
            'order_status' =>  array(OrderService::$status_completed),
            'order_date_between' => array('from' => $dateFrom, 'to' => $dateTo),
        );

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array(
                'pickup' => array(),
                'self_delivered' => array(),
                'our_deliver' => array(),
                'total' => 0,
            );
        }

        $ordersGrouped = array(
            'pickup' => array(),
            'self_delivered' => array(),
            'our_deliver' => array(),
            'total' => count($orders),
        );

        foreach ($orders as $order) {
            if ($order->getDeliveryType() == 'pickup') {
                $ordersGrouped['pickup'][] = $order;
            } elseif ($order->getPlacePointSelfDelivery()) {
                $ordersGrouped['self_delivered'][] = $order;
            } else {
                $ordersGrouped['our_deliver'][] = $order;
            }
        }

        return $ordersGrouped;
    }

    /**
     * @param string $city
     * @param int $id
     * @return bool
     */
    public function hasNewUnassignedOrder($city, $id)
    {
        $filter = array(
            'order_status' =>  array(
                OrderService::$status_accepted,
                OrderService::$status_delayed,
                OrderService::$status_finished
            ),
            'place_point_city' => $city,
            'deliveryType' => OrderService::$deliveryDeliver,
        );
        $order = $this->getOrdersByFilter($filter, 'single');

        if (!$order) {
            return false;
        }

        $order = $order[0];

        if ($order->getId() > $id) {
            return true;
        }
        return false;
    }

    /**
     * @param string $city
     * @param int $id
     * @return bool
     */
    public function hasNewUnconfirmedOrder($city, $id)
    {
        $filter = array(
            'order_status' =>  array(OrderService::$status_new),
            'place_point_city' => $city,
            'deliveryType' => OrderService::$deliveryDeliver,
        );
        $order = $this->getOrdersByFilter($filter, 'single');

        if (!$order) {
            return false;
        }

        $order = $order[0];

        if ($order->getId() > $id) {
            return true;
        }
        return false;
    }

    /**
     * @param array $filter
     * @param string $type Type of result expected. Available: ['list', 'single']
     * @throws \InvalidArgumentException
     * @return array|\Food\OrderBundle\Entity\Order[]
     */
    protected function getOrdersByFilter($filter, $type = 'list')
    {
        if (!in_array($type, array('list', 'single'))) {
            throw new \InvalidArgumentException('Unknown query type, dude');
        }

        if ($type == 'list') {
            $qb = $this->createQueryBuilder('o');

            /**
             * @var QueryBuilder $qb
             */
            $qb->where('1 = 1');

            foreach ($filter as $filterName => $filterValue) {
                switch($filterName) {
                    case 'order_date_more':
                        $qb->andWhere('o.order_date < :'.$filterName);
                        break;

                    case 'order_date_between':
                        $qb->andWhere('o.order_date BETWEEN :order_date_between_from AND :order_date_between_to');
                        unset($filter['order_date_between']);
                        $filter['order_date_between_from'] = $filterValue['from'];
                        $filter['order_date_between_to'] = $filterValue['to'];
                        break;

                    case 'order_status':
                        $qb->andWhere('o.'.$filterName.' IN (:'.$filterName.')');
                        break;

                    default:
                        $qb->andWhere('o.'.$filterName.' = :'.$filterName);
                        break;
                }
            }

            $qb->setParameters($filter)
                ->orderBy('o.order_date', 'ASC');

            $orders = $qb->getQuery()
                ->getResult();
        } else {
            $orders = $this->findBy(
                $filter,
                array('order_date' => 'DESC'),
                1
            );
        }

        return $orders;
    }

    /**
     * @return array
     */
    public function getForgottenOrders()
    {
        $dateFrom = date("Y-m-d H:i:00", strtotime('-30 minute'));
        $dateTo = date("Y-m-d H:i:00", strtotime('-16 minute'));

        $query = "
            SELECT
                `id`
            FROM  `orders`
            WHERE
              `order_date` >= '{$dateFrom}'
              AND `order_date` <=  '{$dateTo}'
              AND `order_status` =  '".OrderService::$status_new."'
              AND `payment_status` = '".OrderService::$paymentStatusComplete."'
              AND (`reminded` != 1 OR `reminded` IS NULL)
        ";

        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($query);

        $stmt->execute();
        $orders = $stmt->fetchAll();

        return $orders;
    }

    /**
     * @return array
     */
    public function getDriversMonthlyOrderCount()
    {
        $dateFrom = date("Y-m-01 00:00:00", strtotime('-1 month'));
        $dateTo = date("Y-m-t 23:59:59", strtotime('-1 month'));

        // TODO statusus pasiimti is OrderService
        $query = "
            SELECT
                d.`id`,
                d.`name`,
                COUNT(  `orders`.`id` ) AS  `total_orders`,
                SUM( IF(  `orders`.`payment_method` =  'local', 1, 0 ) ) AS  `local_payments` ,
                SUM( IF(  `orders`.`payment_method` =  'local', 0, 1 ) ) AS  `external_payments` ,
                SUM( IF(  `orders`.`payment_method` =  'local',  `orders`.`total` , 0 ) ) AS  `total_local` ,
                SUM( IF(  `orders`.`payment_method` =  'local', 0,  `orders`.`total` ) ) AS  `total_external`,
                SUM(`orders`.`total`) AS `order_total_sum`
            FROM  `orders`
            LEFT JOIN  `drivers` d ON d.id =  `orders`.`driver_id`
            WHERE  `order_status` =  'completed'
                AND  `delivery_type` =  'deliver'
                AND  `place_point_self_delivery` =  '0'
                AND  `driver_id` IS NOT NULL
                AND  `order_date` >=  '{$dateFrom}'
                AND  `order_date` <=  '{$dateTo}'
            GROUP BY  `driver_id`
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        $ordersGrouped = $stmt->fetchAll();

        return $ordersGrouped;
    }
}