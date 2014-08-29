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
            // Spajunam i tai, kad vairuotojas deleted ir gaunam ji, jop sikt mat, skant..
            $driverRepo = $this->getEntityManager()->getRepository('FoodAppBundle:Driver');

            $order->setDriverSafe(
                $driverRepo->getDriverPxIfDeleted($order->getId())
            );

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
        return $stmt->fetchAll();
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array $placeIds
     * @param bool $groupMonth
     *
     * @return array
     */
    public function getPlacesOrderCountForRange($dateFrom, $dateTo, $placeIds = array(), $groupMonth=false)
    {
        $orderStatus = OrderService::$status_completed;
        $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
        $dateTo = $dateTo->format("Y-m-d 00:00:01");

        $placesFilter = '';
        if (!empty($placeIds)) {
            $placesFilter = ' AND o.place_id IN ('.implode(', ', $placeIds).')';
        }

        $groupByMonthDate = $groupByMonth = $groupByMonthOrder = '';
        if ($groupMonth) {
            $groupByMonthDate = ', DATE_FORMAT(o.order_date, "%Y-%m") AS month';
            $groupByMonth = ', DATE_FORMAT(o.order_date, "%Y-%m")';
            $groupByMonthOrder = 'DATE_FORMAT(o.order_date, "%Y-%m") DESC, ';
        }

        $query = "
          SELECT
            o.place_id,
            p.name AS place_name,
            COUNT(o.id) AS order_count,
            SUM(o.total) AS order_sum,
            SUM(
              IF(o.delivery_type = 'deliver', 1, 0)
            ) AS deliver_count,
            SUM(
              IF(o.delivery_type = 'pickup', 1, 0)
            ) AS pickup_count
            {$groupByMonthDate}
          FROM orders o
          LEFT JOIN place p ON p.id = o.place_id
          WHERE
            o.order_status = '{$orderStatus}'
            AND (o.order_date BETWEEN '{$dateFrom}' AND '{$dateTo}')
            {$placesFilter}
          GROUP BY o.place_id{$groupByMonth}
          ORDER BY {$groupByMonthOrder}order_count DESC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return array
     */
    public function getOrderCountByDay($dateFrom, $dateTo)
    {
        $orderStatus = OrderService::$status_completed;
        $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
        $dateTo = $dateTo->format("Y-m-d 00:00:01");

        $query = "
          SELECT
            DATE_FORMAT(o.order_date, '%m-%d') AS report_day,
            COUNT(o.id) AS order_count
          FROM orders o
          WHERE
            o.order_status = '{$orderStatus}'
            AND (o.order_date BETWEEN '{$dateFrom}' AND '{$dateTo}')
          GROUP BY DATE_FORMAT(o.order_date, '%m-%d')
          ORDER BY DATE_FORMAT(o.order_date, '%m-%d') ASC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}