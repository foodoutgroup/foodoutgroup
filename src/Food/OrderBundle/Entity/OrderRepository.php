<?php

namespace Food\OrderBundle\Entity;
use Doctrine\ORM\EntityRepository;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\Validator\Constraints\DateTime;

class OrderRepository extends EntityRepository
{
    /**
     * @param string $city
     * @return array|Order[]
     */
    public function getOrdersUnassigned($city)
    {
        $date = new \DateTime();
        $date->modify("-2 minutes");

        $filter = array(
            'order_status' =>  array(
                OrderService::$status_accepted,
                OrderService::$status_delayed,
                OrderService::$status_finished,
                OrderService::$status_forwarded,
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
     * @return array|Order[]
     */
    public function getOrdersUnapproved($city)
    {
        $date = new \DateTime();
        $date->modify("-2 minutes");

        $filter = array(
            'order_status' =>  array(
                OrderService::$status_unapproved,
            ),
            'place_point_city' => $city,
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
     * @param boolean $pickup
     * @return array|Order[]
     */
    public function getOrdersUnconfirmed($city, $pickup = false)
    {
        $filter = array(
            'order_status' =>  array(OrderService::$status_new),
            'place_point_city' => $city,
            'deliveryType' => (!$pickup ? OrderService::$deliveryDeliver : OrderService::$deliveryPickup),
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
     * @return array|Order[]
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
     * @param string $city
     * @return array|Order[]
     */
    public function getOrdersCanceled($city)
    {
        $filter = array(
            'order_status' =>  OrderService::$status_canceled,
            'place_point_city' => $city,
            'deliveryType' => OrderService::$deliveryDeliver,
            'paymentStatus' => OrderService::$paymentStatusComplete,
            'order_date_between' => array(
                'from' => new \DateTime('-4 hour'),
                'to' => new \DateTime('now'),
            ),
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
     * @param string|null $city
     * @param int|null $id
     * @return bool
     */
    public function hasNewUnassignedOrder($city=null, $id=null)
    {
        $filter = array(
            'order_status' =>  array(
                OrderService::$status_accepted,
                OrderService::$status_delayed,
                OrderService::$status_finished
            ),
            'deliveryType' => OrderService::$deliveryDeliver,
        );
        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

        $order = $this->getOrdersByFilter($filter, 'single');

        if (!$order) {
            return false;
        }

        if (empty($id)) {
            return true;
        }

        $order = $order[0];

        if ($order->getId() > $id) {
            return true;
        }
        return false;
    }

    /**
     * @param string|null $city
     * @param int|null $id
     * @return bool
     */
    public function hasNewUnconfirmedOrder($city=null, $id=null)
    {
        $filter = array(
            'order_status' =>  array(OrderService::$status_new),
            'deliveryType' => OrderService::$deliveryDeliver,
        );
        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

        $order = $this->getOrdersByFilter($filter, 'single');

        if (!$order) {
            return false;
        }

        if (empty($id)) {
            return true;
        }

        $order = $order[0];

        if ($order->getId() > $id) {
            return true;
        }
        return false;
    }

    /**
     * Gets orders with nav_problem statuses for a given period
     *
     * @param string $dateStart
     * @param string $dateEnd
     * @return array|Order[]
     */
    public function getNavProblems($dateStart, $dateEnd)
    {
        $filter = array(
            'order_status' =>  array(OrderService::$status_nav_problems),
            'order_date_between' => array(
                'from' => $dateStart,
                'to' => $dateEnd,
            ),
            'only_to_nav' => 1,
        );
        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
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

                    case 'not_nav':
                        $qb->andWhere('o.orderFromNav != :'.$filterName);
                        break;

                    case 'only_to_nav':
                        $qb->leftJoin('o.place', 'p', 'o.place = p.id')
                            ->andWhere('p.navision = 1');
                        unset($filter['only_to_nav']);
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
                array(
                    'order_date' => 'DESC',
                ),
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
        $dateTo = $dateTo->format("Y-m-d 23:59:59");

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
            p.self_delivery AS self_delivery,
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
     * @param string $orderStatus
     * @return array
     */
    public function getOrderCountByDay($dateFrom, $dateTo, $orderStatus=null, $mobile=false)
    {
        if (empty($orderStatus)) {
            $orderStatus = "'".OrderService::$status_completed."', '".OrderService::$status_partialy_completed."'";
        } else {
            $orderStatus = "'".$orderStatus."'";
        }

        $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
        $dateTo = $dateTo->format("Y-m-d 23:59:59");

        $query = "
          SELECT
            DATE_FORMAT(o.order_date, '%y-%m-%d') AS report_day,
            COUNT(o.id) AS order_count
          FROM orders o
          WHERE
            o.order_status IN ({$orderStatus})
            AND (o.order_date BETWEEN '{$dateFrom}' AND '{$dateTo}')
            ".($mobile ? 'AND mobile=1':'')."
          GROUP BY DATE_FORMAT(o.order_date, '%y-%m-%d')
          ORDER BY DATE_FORMAT(o.order_date, '%y-%m-%d') ASC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return Order[]|array
     */
    public function getUnclosedOrders()
    {
        $orderStatus = "'".OrderService::$status_accepted
            ."', '".OrderService::$status_assiged
            ."', '".OrderService::$status_finished
            ."', '".OrderService::$status_delayed."'";
        $paymentStatus = OrderService::$paymentStatusComplete;
        $pickup = OrderService::$deliveryPickup;
        $deliver = OrderService::$deliveryDeliver;

        $dateFrom = new \DateTime("now");
        $dateToPickup = new \DateTime("-65 minute");
        $dateToDeliver = new \DateTime("-90 minute");

        $dateFrom1 = $dateFrom->sub(new \DateInterval('PT12H'))->format("Y-m-d h:i:s");
        $dateToPickup = $dateToPickup->format("Y-m-d h:i:s");
        $dateToDeliver = $dateToDeliver->format("Y-m-d h:i:s");

        $query = "
          SELECT
            o.id,
            o.delivery_time
          FROM orders o
          WHERE
            o.order_status IN ({$orderStatus})
            AND o.payment_status = '{$paymentStatus}'
            AND (
              (
                o.delivery_type = '{$pickup}'
                AND o.delivery_time BETWEEN '{$dateFrom1}' AND '{$dateToPickup}'
              )
              OR
              (
               o.delivery_type = '{$deliver}'
                AND o.delivery_time BETWEEN '{$dateFrom1}' AND '{$dateToDeliver}'
              )
            )
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param $timeBack string|null
     * @param boolean $skipImportedFromNav
     * @param boolean $excludeCompleted
     * @return array
     */
    public function getCurrentNavOrders($timeBack = null, $skipImportedFromNav = false, $excludeCompleted = true)
    {
        if (empty($timeBack)) {
            $timeBack = '-1 day';
        }
        $qb = $this->createQueryBuilder('o');

        $excludeStatuses = [
            OrderService::$status_canceled,
            OrderService::$status_nav_problems,
            OrderService::$status_pre,
            OrderService::$status_unapproved,
            // TODO temp, nav canot cancel assigned orders
//            OrderService::$status_assiged
        ];

        if ($excludeCompleted) {
            $excludeStatuses[] = OrderService::$status_completed;
        }

        $qb->leftJoin('o.place', 'p')
            ->where('o.order_date >= :order_date')
            ->andWhere('p.navision = :navision')
            ->andWhere('o.order_status NOT IN (:order_status)')
            ->andWhere('o.paymentStatus = :payment_status')
            ->setParameters(array(
                'order_date' => new \DateTime($timeBack),
                'order_status' => $excludeStatuses,
                'navision' => 1,
                'payment_status' => OrderService::$paymentStatusComplete,
            ));

        if ($skipImportedFromNav) {
            $qb->andWhere('o.orderFromNav != 1');
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTime $date
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getOrdersToBeLate($date)
    {
        $orderStatus = "'".OrderService::$status_accepted
            ."', '".OrderService::$status_assiged
            ."', '".OrderService::$status_finished
            ."', '".OrderService::$status_delayed."'";
        $paymentStatus = OrderService::$paymentStatusComplete;
        $deliveryType = OrderService::$deliveryDeliver;

        $dateFrom = new \DateTime("-2 hour");
        $dateFrom = $dateFrom->format("Y-m-d H:i:s");
        $dateTo = $date->format("Y-m-d H:i:s");

        $query = "
          SELECT
            o.id
          FROM orders o
          WHERE
            o.order_status IN ({$orderStatus})
            AND o.payment_status = '{$paymentStatus}'
            AND o.delivery_type = '{$deliveryType}'
            AND o.delivery_time BETWEEN '{$dateFrom}' AND '{$dateTo}'
            AND o.place_point_self_delivery != 1
            AND (o.late_order_informed != 1 OR o.late_order_informed IS NULL)
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get completed orders by phone
     *
     * @param srting $phone
     * @return array|Order[]
     */
    public function getCompletedOrdersByPhone($phone)
    {
        $qb = $this->createQueryBuilder('o');

        $qb->leftJoin('o.user', 'u')
            ->where('o.order_status IN (:order_status)')
            ->andWhere('u.phone = :phone_no')
            ->setParameters(array(
                'order_status' => array(OrderService::$status_completed, OrderService::$status_partialy_completed),
                'phone_no' => $phone,
            ));

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @return array|Order[]
     */
    public function getCorporateOrdersForInvoice()
    {
        $qb = $this->createQueryBuilder('o');

        $qb->where('o.order_status IN (:order_status)')
        ->andWhere('o.isCorporateClient = :corporate_cl')
        ->andWhere('o.order_date BETWEEN :date_start AND :date_end')
        ->andWhere('o.sfNumber IS NULL')
        ->setParameters(array(
            'order_status' => array(OrderService::$status_completed, OrderService::$status_partialy_completed),
            'corporate_cl' => 1,
            'date_start' => new \DateTime(date("Y-m-01 00:00:01")),
            'date_end' => new \DateTime("now")
        ));

        return $qb->getQuery()
            ->getResult();
    }
}
