<?php

namespace Food\MonitoringBundle\Service;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class MonitoringService
 * @package Food\SmsBundle\Service
 */
class MonitoringService extends ContainerAware {


    /**
     * @var null
     */
    private $manager = null;

    /**
     * @param null $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager()
    {
        if (empty($this->manager)) {
            $this->manager = $this->container->get('doctrine')->getManager();
        }
        return $this->manager;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getUnfinishedOrdersForRange(\DateTime $from, \DateTime $to)
    {
        $repository = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $query = $repository->createQueryBuilder('o')
            ->where('o.order_status IN (:order_status)')
            ->andWhere('o.paymentStatus = :payment_status')
            ->andWhere('o.order_date >= :from_date')
            ->andWhere('o.order_date <= :to_date')
            ->andWhere('o.order_date < :max_delivery_date')
            ->orderBy('o.order_date', 'ASC')
            ->setParameters(
                [
                    'order_status' => array(
                        OrderService::$status_accepted,
                        OrderService::$status_delayed,
                        OrderService::$status_finished,
                        OrderService::$status_assiged,
                    ),
                    'payment_status' => OrderService::$paymentStatusComplete,
                    'from_date' => $from,
                    'to_date' => $to,
                    'max_delivery_date' => new \DateTime('-4 hour')
                ]
            )
            ->getQuery();

        $orders = $query->getResult();
        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @return Order[]|array
     */
    public function getUnacceptedOrders()
    {
        $repository = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $query = $repository->createQueryBuilder('o')
            ->where('o.order_status = :order_status')
            ->andWhere('o.paymentStatus = :payment_status')
            ->andWhere('o.order_date <= :date')
            ->andWhere('o.order_date > :oldest_date')
            ->orderBy('o.order_date', 'ASC')
            ->setParameters(
                [
                    'order_status' => OrderService::$status_new,
                    'payment_status' => OrderService::$paymentStatusComplete,
                    'date' => new \DateTime("-22 minute"),
                    'oldest_date' => new \DateTime("-1 day")
                ]
            )
            ->getQuery();

        $orders = $query->getResult();
        if (!$orders) {
            return array();
        }

        return $orders;
    }

    /**
     * @return Order[]|array
     */
    public function getUnassignedOrders()
    {
        $repository = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order');

        $query = $repository->createQueryBuilder('o')
            ->where('o.order_status IN (:order_status)')
            ->andWhere('o.paymentStatus = :payment_status')
            ->andWhere('o.deliveryTime <= :date')
            ->andWhere('o.deliveryTime > :oldest_date')
            ->andWhere('o.place_point_self_delivery != 1')
            ->andWhere('o.deliveryType != :delivery_type')
            ->orderBy('o.order_date', 'ASC')
            ->setParameters(
                [
                    'order_status' => array(
                        OrderService::$status_accepted,
                        OrderService::$status_delayed,
                        OrderService::$status_forwarded,
                    ),
                    'payment_status' => OrderService::$paymentStatusComplete,
                    'date' => new \DateTime("+25 minute"),
                    'oldest_date' => new \DateTime("-1 day"),
                    'delivery_type' => OrderService::$deliveryPickup,
                ]
            )
            ->getQuery();

        $orders = $query->getResult();
        if (!$orders) {
            return array();
        }

        return $orders;
    }
}