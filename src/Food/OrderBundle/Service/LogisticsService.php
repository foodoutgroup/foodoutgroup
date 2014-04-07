<?php

namespace Food\OrderBundle\Service;

use Food\AppBundle\Entity\Driver;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class LogisticsService
 * All the logistic logic is somewhere around here
 *
 * @package Food\OrderBundle\Service
 */
class LogisticsService extends ContainerAware
{
    /**
     * @var string Possible values: 'local', 'etaxi'
     */
    private $logisticSystem = 'local';

    /**
     * @var OrderService
     */
    private $orderService = null;

    /**
     * @param string $logisticSystem
     */
    public function setLogisticSystem($logisticSystem)
    {
        $this->logisticSystem = $logisticSystem;
    }

    /**
     * @return string
     */
    public function getLogisticSystem()
    {
        return $this->logisticSystem;
    }

    /**
     * @param \Food\OrderBundle\Service\OrderService $orderService
     */
    public function setOrderService($orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * @return \Food\OrderBundle\Service\OrderService
     */
    public function getOrderService()
    {
        return $this->orderService;
    }

    /**
     * Returns the list of available drivers
     *
     * @param float $lat
     * @param float $lon
     * @param string $city
     *
     * @return array
     */
    public function getDrivers($lat, $lon, $city)
    {
        switch($this->getLogisticSystem()) {
            case 'etaxi':
                $drivers = $this->getDriversExternal($lat, $lon);
            break;

            default:
                $drivers = $this->getDriversLocal($city);
            break;
        }

        return $drivers;
    }

    /**
     * Get localy stored drivers as external system is not working at all :(
     *
     * @param string $city
     *
     * @return array
     */
    protected function getDriversLocal($city)
    {
        $em = $this->container->get('doctrine')->getManager();
        $drivers = $em->getRepository('Food\AppBundle\Entity\Driver')
            ->findBy(array(
                'active' => true,
                'city' => $city,
            ));

        if (!$drivers) {
            return array();
        }

        return $drivers;
    }

    /**
     * Get drivers from external system
     *
     * @param float $lat
     * @param float $lon
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getDriversExternal($lat, $lon)
    {
        // TODO implement me with etaxi and other possible flows
        throw new \Exception('I are not implemented yet');
        return array();
    }

    /**
     * @param int $id
     * @return Driver|bool
     */
    public function getDriverById($id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $driver = $em->getRepository('Food\AppBundle\Entity\Driver')->find($id);

        if (!$driver) {
            return false;
        }

        return $driver;
    }

    /**
     * @param $driverId
     * @param $orderIds
     */
    public function assignDriver($driverId, $orderIds)
    {
        $logger = $this->container->get('logger');
        $logger->alert('++ assignDriver');
        $logger->alert('driverId: '.$driverId);
        $driver = $this->getDriverById($driverId);
        $orderService = $this->getOrderService();

        foreach($orderIds as $orderId) {
            $order = $orderService->getOrderById($orderId);
            $order->setDriver($driver);

            $orderService->statusAccepted();
            $orderService->saveOrder();
        }

        // TODO etaksi assigninimas
//        switch($this->getLogisticSystem()) {
//            case 'etaxi':
//                break;
//
//            default:
//                break;
//        }
    }
}