<?php

namespace Food\OrderBundle\Entity;
use Doctrine\ORM\EntityRepository;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\Validator\Constraints\DateTime;

class OrderRepository extends EntityRepository
{
    /**
     * @param string|null $city
     * @return array|Order[]
     */
    public function getOrdersUnassigned($city = null)
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
            'deliveryType' => OrderService::$deliveryDeliver,
            'order_date_more' => $date,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }
    /**
     * @param string|null $city
     * @return array|Order[]
     */
    public function getOrdersUnapproved($city = null)
    {
        $date = new \DateTime();
        $date->modify("-2 minutes");

        $filter = array(
            'order_status' =>  array(
                OrderService::$status_unapproved,
            ),
            'order_date_more' => $date,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );
        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string|null $city
     * @param boolean $pickup
     * @param boolean $forceBoth
     * @return array|Order[]
     */
    public function getOrdersUnconfirmed($city=null, $pickup = false, $forceBoth=false)
    {
        $filter = array(
            'order_status' =>  array(OrderService::$status_new, OrderService::$status_preorder),
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        if (!$forceBoth) {
            $filter['deliveryType'] = (!$pickup ? OrderService::$deliveryDeliver : OrderService::$deliveryPickup);
        }

        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string|null $city
     * @return array|Order[]
     */
    public function getOrdersAssigned($city=null)
    {
        $filter = array(
            'order_status' => OrderService::$status_assiged,
            'deliveryType' => OrderService::$deliveryDeliver,
            // @TODO enable only after successfull sync between nav otherwise
            // lot of hanging deliveries without possibility to close
            //~ 'undelivered' => true,
            'paymentStatus' => OrderService::$paymentStatusComplete,
        );

        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string|null $city
     * @return array|Order[]
     */
    public function getOrdersCanceled($city=null)
    {
        $filter = array(
            'order_status' =>  OrderService::$status_canceled,
            'deliveryType' => OrderService::$deliveryDeliver,
            'paymentStatus' => OrderService::$paymentStatusComplete,
            'order_date_between_with_preorder' => array(
                'from' => new \DateTime('-4 hour'),
                'to' => new \DateTime('now'),
            ),
        );

        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

        $orders = $this->getOrdersByFilter($filter, 'list');

        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @param string|null $city
     * @return array|Order[]
     */
    public function getOrdersProblems($city=null)
    {
        $filter = array(
            'is_problem' => true,
            'order_date_between' => array(
                'from' => new \DateTime('-4 hour'),
                'to' => new \DateTime('now'),
            ),
            'not_solved' => true,
        );

        if (!empty($city)) {
            $filter['place_point_city'] = $city;
        }

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
            'daily_grouped_report' =>  true,
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

                    case 'order_date_between_with_preorder':
                        $qb->andWhere('(o.order_date BETWEEN :order_date_between_from AND :order_date_between_to) OR (o.preorder = 1 AND o.deliveryTime BETWEEN :order_date_between_from_pre AND :order_date_between_to_pre)');
                        unset($filter['order_date_between_with_preorder']);
                        $filter['order_date_between_from'] = $filterValue['from'];
                        $filter['order_date_between_to'] = $filterValue['to'];
                        $filter['order_date_between_from_pre'] = $filterValue['from'];
                        $filter['order_date_between_to_pre'] = $filterValue['to'];
                        break;

                    case 'order_status':
                        $qb->andWhere('o.'.$filterName.' IN (:'.$filterName.')');
                        break;

                    case 'not_nav':
                        $qb->andWhere('o.orderFromNav != :'.$filterName);
                        break;

                    case 'is_problem':
                        $qb->andWhere('o.order_status = :problem_status OR (o.paymentStatus IN (:problem_payment_status) AND o.order_date > :problem_date_time AND o.order_status != :problem_excluded_status)');
                        unset($filter['is_problem']);
                        $filter['problem_status'] = OrderService::$status_nav_problems;
                        $filter['problem_excluded_status'] = OrderService::$status_pre;
                        $filter['problem_payment_status'] = array(OrderService::$paymentStatusWait, OrderService::$paymentStatusWaitFunds);
                        $filter['problem_date_time'] = new \DateTime('-5 minute');
                        break;

                    case 'undelivered':
                        $qb->andWhere('(o.order_status = :status_assigned AND o.deliveryType = :deliveryDeliver) OR (o.order_status IN (:accepted_statuses) AND o.deliveryType = :deliveryPickup)');
                        unset($filter['undelivered']);
                        $filter['status_assigned'] = OrderService::$status_assiged;
                        $filter['accepted_statuses'] = array(OrderService::$status_accepted, OrderService::$status_finished);
                        $filter['deliveryDeliver'] = OrderService::$deliveryDeliver;
                        $filter['deliveryPickup'] = OrderService::$deliveryPickup;
                        break;

                    case 'not_solved':
                        $qb->andWhere('(o.problemSolved != 1 OR o.problemSolved IS NULL)');
                        unset($filter['not_solved']);
                        break;

                    case 'only_to_nav':
                        $qb->leftJoin('o.place', 'p', 'o.place = p.id')
                            ->andWhere('p.navision = 1');
                        unset($filter['only_to_nav']);
                        break;

                    case 'daily_grouped_report':
                        $qb->andWhere('o.order_status = :problem_status OR (o.preorder = 1 AND o.order_status NOT IN (:preorder_status_list))');
                        unset($filter['daily_grouped_report']);
                        $filter['problem_status'] = OrderService::$status_completed;
                        $filter['preorder_status_list'] = array(OrderService::$status_canceled, OrderService::$status_failed);
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
            o.place_name AS place_name,
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
          GROUP BY COALESCE(o.place_id, o.place_name){$groupByMonth}
          ORDER BY {$groupByMonthOrder}order_count DESC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array $placeIds
     * @param bool $groupDay
     *
     * @return array
     */
    public function getLatencyReport($dateFrom, $dateTo, $placeIds = array(), $groupDay=false)
    {
        $orderStatus = "'".OrderService::$status_completed."', '".OrderService::$status_partialy_completed."'";
        $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
        $dateTo = $dateTo->format("Y-m-d 23:59:59");

        $placesFilter = '';
        if (!empty($placeIds)) {
            $placesFilter = ' AND o.place_id IN ('.implode(', ', $placeIds).')';
        }

        $groupByDayDate = $groupByDay = $groupByDayOrder = '';
        if ($groupDay) {
            $groupByDayDate = 'DATE_FORMAT(o.order_date, "%Y-%m-%d") AS day,';
            $groupByDay = ', DATE_FORMAT(o.order_date, "%Y-%m-%d")';
            $groupByDayOrder = 'DATE_FORMAT(o.order_date, "%Y-%m-%d") DESC, ';
        }

        $query = "
          SELECT
              COUNT( o.id ) AS  'orders_in_question',
              p.`name` AS 'place_name',
              p.`id` AS 'place_id',
              {$groupByDayDate}
              AVG(
                (
                  SELECT (`since_last` /60)
                  FROM order_delivery_log
                  WHERE order_id = o.id
                    AND  `event` =  'order_accepted'
                  LIMIT 1
                  )
                ) AS  'accepted_in',
              AVG(
                (
                    SELECT (`since_last` /60)
                    FROM order_delivery_log
                    WHERE order_id = o.id
                      AND  `event` =  'order_finished'
                    LIMIT 1
                  )
                ) AS  'finished_in',
            AVG(
                (
                    SELECT (`since_last` /60)
                    FROM order_delivery_log
                    WHERE order_id = o.id
                      AND  `event` =  'order_assigned'
                    LIMIT 1
                  )
              ) AS  'assigned_in',
            AVG(
              (
                    SELECT (`since_last` /60)
                    FROM order_delivery_log
                    WHERE order_id = o.id
                      AND  `event` =  'order_pickedup'
                    LIMIT 1
                  )
              ) AS  'pickedup_in',
            AVG(
              (
                    SELECT (`since_last` /60)
                    FROM order_delivery_log
                    WHERE order_id = o.id
                      AND  `event` =  'order_completed'
                    LIMIT 1
                  )
              ) AS  'completed_in',
            AVG(
              (
                    SELECT (TIMEDIFF(`event_date`, o.`order_date`))/60
                    FROM order_delivery_log
                    WHERE order_id = o.id
                      AND `event` = 'order_completed'
                    LIMIT 1
                )
              ) AS 'order_in'
            FROM  `orders` o
            LEFT JOIN  `place` p ON p.id = o.`place_id`
            WHERE
              o.order_date BETWEEN '{$dateFrom}' AND '{$dateTo}'
              AND o.order_status IN ({$orderStatus})
              {$placesFilter}
            GROUP BY o.`place_id`{$groupByDay}
            ORDER BY {$groupByDayOrder} 'accepted_in' DESC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSlowestOrderForEvent($event, $placeId, $dateFrom, $dateTo)
    {
        $orderStatus = "'".OrderService::$status_completed."', '".OrderService::$status_partialy_completed."'";
        $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
        $dateTo = $dateTo->format("Y-m-d 23:59:59");

        $placesFilter = ' AND o.place_id = "'.$placeId.'"';

        $query = "
          SELECT
              o.id AS 'order_id',
              (odl.`since_last` / 60) AS 'duration'
            FROM  `orders` o
            LEFT JOIN `place` p ON p.id = o.`place_id`
            LEFT JOIN `order_delivery_log` odl ON odl.`order_id` = o.`id`
            WHERE
              o.order_date BETWEEN '{$dateFrom}' AND '{$dateTo}'
              AND o.order_status IN ({$orderStatus})
              AND odl.event = '{$event}'
              {$placesFilter}
            ORDER BY odl.`since_last` DESC
            LIMIT 5
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return  $stmt->fetchAll();;
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

        $dateFrom1 = $dateFrom->sub(new \DateInterval('PT12H'))->format("Y-m-d H:i:s");
        $dateToPickup = $dateToPickup->format("Y-m-d H:i:s");
        $dateToDeliver = $dateToDeliver->format("Y-m-d H:i:s");

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
     * @return array
     */
    public function getPreOrders()
    {
        $paymentStatus = OrderService::$paymentStatusComplete;
        $orderStatus = OrderService::$status_preorder;

        /**
         * Da logic:
         *
         * imam tuos uzsakymus, kurie:
         *  - statusas - preorder
         *  - payment - completed
         *  - delivery type - any
         *  - kai iki uzsakymo liko valanda +- crono laikas (in case shit happened ir reikia vel tvarkyti orderi - be ready for that)
         */

        $dateFrom = new \DateTime("+40 minute");
        $dateTo = new \DateTime("+69 minute");

        $dateFrom = $dateFrom->format("Y-m-d H:i:s");
        $dateTo = $dateTo->format("Y-m-d H:i:s");

        $query = "
          SELECT
            o.id,
            o.delivery_time
          FROM orders o
          LEFT JOIN place p ON p.id = o.place_id
          WHERE
            o.order_status IN ('{$orderStatus}')
            AND o.payment_status = '{$paymentStatus}'
            AND o.delivery_time BETWEEN '{$dateFrom}' AND '{$dateTo}'
            AND (p.navision != 1 OR p.navision IS NULL)
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
        $timeBackPreorder = '-3 day';
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
            ->where('(o.order_date >= :order_date AND (o.preorder = 0 OR o.preorder IS NULL)) OR (o.order_date >= :pre_order_date AND o.preorder = 1)')
            ->andWhere('p.navision = :navision')
            ->andWhere('o.order_status NOT IN (:order_status)')
            ->andWhere('o.paymentStatus = :payment_status')
            ->setParameters(array(
                'order_date' => new \DateTime($timeBack),
                'pre_order_date' => new \DateTime($timeBackPreorder),
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

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array $placeIds
     * @param bool $groupMonth
     *
     * @return array
     */
    public function getPlacesOrdersForRange($dateFrom = false, $dateTo = false, $placeIds = array(), $groupMonth=false)
    {
        $orderStatus = OrderService::$status_completed;
        $dates_filter = "";
        if (!empty($dateFrom) && !empty($dateTo)) {
            $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
            $dateTo = $dateTo->format("Y-m-d 23:59:59");
            $dates_filter = " AND (o.order_date BETWEEN '".$dateFrom."' AND '".$dateTo."')";
        }

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
            o.id,
            p.name AS place_name,
            o.order_date,
            o.total,
            o.place_point_address,
            o.order_status,
            o.payment_status,
            o.delivery_type,
            o.accept_time,
            (select event_date from order_delivery_log odl where odl.order_id = o.id AND odl.event = 'order_pickedup' LIMIT 1) as delivery_pickup_time,
            o.delivery_time
            {$groupByMonthDate}
          FROM orders o
          LEFT JOIN place p ON p.id = o.place_id
          WHERE
            o.order_status = '{$orderStatus}'
            {$dates_filter}
            {$placesFilter}
          GROUP BY o.id{$groupByMonth}
          ORDER BY {$groupByMonthOrder} ". (empty($placesFilter) ? ' o.place_name ASC, o.id DESC ' : ' o.id DESC ') ."
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param bool|false $order_id
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getOrderDetails($order_id = false)
    {
        $query = "SELECT
            od.id,
            od.dish_name AS dish_name,
            od.price AS price,
            od.quantity AS quantity,
            od.dish_unit_name AS dish_unit_name,
            od.dish_size_code AS dish_size_code,
            od.order_id AS order_id
            FROM  order_details od WHERE od.order_id = '{$order_id}'
        ";

        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($query);

        $stmt->execute();
        $orders_detail = $stmt->fetchAll();
        return $orders_detail;
    }
}
