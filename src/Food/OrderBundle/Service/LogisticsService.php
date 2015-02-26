<?php

namespace Food\OrderBundle\Service;

use Food\AppBundle\Entity\Driver;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderToLogistics;
use Symfony\Component\DependencyInjection\ContainerAware;
use Curl;

/**
 * Class LogisticsService
 * All the logistic logic is somewhere around here
 *
 * @package Food\OrderBundle\Service
 */
class LogisticsService extends ContainerAware
{
    /**
     * @var string Possible values: 'local', 'etaxi', 'external'
     */
    private $logisticSystem = 'local';

    /**
     * @var OrderService
     */
    private $orderService = null;

    /**
     * Convert order payment method to external logistics method
     * @var array
     */
    private $paymentMethodMap = array(
        'local' => 'local',
        'local.card' => 'local.card',
        'paysera' => 'prepaid',
        'banklink' => 'prepaid',
        'swedbank-gateway' => 'prepaid',
        'swedbank-credit-card-gateway' => 'prepaid',
        'seb-banklink' => 'prepaid',
        'nordea-banklink' => 'prepaid'
    );

    /**
     * @var Curl
     */
    private $_cli;

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
     * @param \Curl $cli
     */
    public function setCli($cli)
    {
        $this->_cli = $cli;
    }

    /**
     * @return \Curl
     */
    public function getCli()
    {
        if (empty($this->_cli)) {
            $this->_cli = new Curl;
            $this->_cli->options['CURLOPT_SSL_VERIFYPEER'] = false;
            $this->_cli->options['CURLOPT_SSL_VERIFYHOST'] = false;
        }
        return $this->_cli;
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
     * @param int $driverId
     * @param array $orderIds
     * @param bool $external
     */
    public function assignDriver($driverId, $orderIds, $external = false)
    {
        $logger = $this->container->get('logger');
        $logger->alert('++ assignDriver');
        $logger->alert('driverId: '.$driverId);
        $driver = $this->getDriverById($driverId);
        $orderService = $this->getOrderService();

        if ($driver && is_array($orderIds)) {
            foreach($orderIds as $orderId) {
                $order = $orderService->getOrderById($orderId);
                $order->setDriver($driver);

                if ($external) {
                    $source = 'logistics_service_external';
                    $logString = 'Driver #%d assigned to order #%d from logistics';
                    $logAction = 'logistics_api_driver_assign';
                } else {
                    $source = 'logistics_service';
                    $logString = 'Driver #%d assigned to order #%d from dispatcher';
                    $logAction = 'dispatcher_driver_assign';
                }

                // Uzloginam prie orderio, kas, kada ir per kur priskyre vairuotoja
                $orderService->logOrder(
                    $order,
                    $logAction,
                    sprintf($logString, $driverId, $orderId)
                );

                // TODO kolkas visad vairuotoja informuojam SMS'u, bet su LogTimeApi nutart ar dubliuojam
                $orderService->statusAssigned($source);
                $orderService->saveOrder();
            }
        }
    }

    /**
     * Get preconfigured Xml writer
     *
     * @return \XMLWriter
     */
    private function getDefaultXmlWriter()
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0','UTF-8');
        $writer->setIndent(true);

        return $writer;
    }

    /**
     * Prepares xml of order for external system
     * @param Order $order
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function generateOrderXml($order)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Cannot generate xml with no order. The road to Mordor is closed');
        }

        $writer = $this->getDefaultXmlWriter();

        $writer->startElement('Order');
        $writer->writeElement('OrderId', $order->getId());

        // Pickup block
        $writer->startElement("PickUp");
        $writer->writeElement('Address', $order->getPlacePointAddress());
        $writer->writeElement('City', $this->convertCityForLogTime($order->getPlacePointCity()));
        $writer->startElement("Coordinates");
        $writer->writeElement('Long', $order->getPlacePoint()->getLon());
        $writer->writeElement('Lat', $order->getPlacePoint()->getLat());
        //End coordinates block
        $writer->endElement();
        $writer->writeElement('PointName', $order->getPlaceName());
        $writer->writeElement('PointId', $order->getPlacePoint()->getId());
        $writer->writeElement('Phone', $order->getPlacePoint()->getPhone());
        // End pickup block
        $writer->endElement();

        // Delivery block
        $writer->startElement("Delivery");
        $writer->writeElement('Address', $order->getAddressId()->getAddress());
        $writer->writeElement('City', $this->convertCityForLogTime($order->getAddressId()->getCity()));
        $writer->writeElement('AddressId', $order->getAddressId()->getId());
        $writer->startElement("Coordinates");
        $writer->writeElement('Long', $order->getAddressId()->getLon());
        $writer->writeElement('Lat', $order->getAddressId()->getLat());
        //End coordinates block
        $writer->endElement();
        $writer->writeElement('CustomerName', $order->getUser()->getFirstname());
        $writer->writeElement('Phone', $order->getUser()->getPhone());
        $writer->writeElement('CustomerComment', $order->getComment());
        // End delivery block
        $writer->endElement();

        // Pickup time
        $acceptTime = $order->getAcceptTime();
        if (empty($acceptTime)) {
            $acceptTime = $order->getOrderDate();
        }

        // If delayed - add delay duration
        if ($order->getDelayed()) {
            $delay = $order->getDelayDuration();
            $acceptTime->add(
                new \DateInterval(sprintf('PT%dM', $delay))
            );
        }

        $pickupToTime = clone $acceptTime;
        $deliveryToTime = clone $acceptTime;

        $writer->startElement("PickUpTime");
        $writer->writeElement('From', $acceptTime->format("Y-m-d H:i"));
        $writer->writeElement('To', $pickupToTime->add(new \DateInterval('PT20M'))->format("Y-m-d H:i"));
        // End pickup time block
        $writer->endElement();

        // Delivery time block
        $writer->startElement("DeliveryTime");
        $writer->writeElement('From', $acceptTime->format("Y-m-d H:i"));
        $writer->writeElement('To', $deliveryToTime->add(new \DateInterval('PT1H'))->format("Y-m-d H:i"));
        // End delivery time block
        $writer->endElement();

        $writer->writeElement('PaymentMethod', $this->convertPaymentMethod($order->getPaymentMethod()));
        $writer->writeElement('Price', $order->getTotal());
        $writer->writeElement('Status', $order->getOrderStatus());

        // Content block
        $writer->startElement("Content");
        foreach ($order->getDetails() as $dish) {
            $writer->startElement("Item");
            $writer->writeElement('Id', $dish->getId());
            $writer->writeElement('Name', $dish->getDishName());
            $writer->writeElement('Qty', $dish->getQuantity());
            $writer->endElement();
        }
        // End content block
        $writer->endElement();

        // End order block
        $writer->endElement();

        $writer->endDocument();
        $xml = $writer->outputMemory(true);

        return $xml;
    }

    /**
     * @param Driver[] $drivers
     * @param array|null $deleted
     * @return string
     */
    public function generateDriverXml($drivers, $deleted = null)
    {
        $writer = $this->getDefaultXmlWriter();

        $writer->startElement('Drivers');

        foreach ($drivers as $driver) {
            $writer->startElement('Driver');
            $writer->writeElement('Id', $driver->getId());
            $writer->writeElement('Phone', $driver->getPhone());
            $writer->writeElement('Name', $driver->getName());
            $writer->writeElement('City', $driver->getCity());
            $writer->writeElement('Active', ($driver->getActive() ? 'Y' : 'N'));
            $writer->endElement();
        }

        if (!empty($deleted) && is_array($deleted)) {
            foreach ($deleted as $driver) {
                $writer->startElement('Driver');
                $writer->writeElement('Id', $driver['id']);
                $writer->writeElement('Phone', $driver['phone']);
                $writer->writeElement('Name', $driver['name']);
                $writer->writeElement('City', $driver['city']);
                $writer->writeElement('Active', 'N');
                $writer->endElement();
            }
        }

        // End drivers block
        $writer->endElement();

        $writer->endDocument();
        $xml = $writer->outputMemory(true);

        return $xml;
    }

    /**
     * @param string $orderMethod
     * @return string
     * @throws \InvalidArgumentException
     */
    public function convertPaymentMethod($orderMethod)
    {
        if (!isset($this->paymentMethodMap[$orderMethod])) {
            throw new \InvalidArgumentException('Unknown payment method: '.$orderMethod);
        }

        return $this->paymentMethodMap[$orderMethod];
    }

    /**
     * Add order to sending stack
     *
     * @param Order $order
     * @throws \InvalidArgumentException
     */
    public function putOrderForSend($order)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Cannot put order to logistis when its not order. Dafuk?');
        }

        // Jeigu ijungtas sendas ir mes vezam ir deliver tipas
        if ($this->container->getParameter('logistics.send_to_external') == true
            && $order->getDeliveryType() == 'deliver'
            && $order->getPlacePointSelfDelivery() == false
            && $order->getPlacePoint()->getUseExternalLogistics()) {
            $logisticsCityFilter = $this->container->getParameter('logistics.city_filter');
            if (empty($logisticsCityFilter) || in_array($order->getPlacePointCity(), $logisticsCityFilter)) {
                $this->container->get('food.order')->logOrder($order, 'schedule_logistics_api_send', 'Order scheduled to send to logistics');

                $om = $this->container->get('doctrine')->getManager();
                $orderToLogistics = new OrderToLogistics();

                $orderToLogistics->setOrder($order)
                    ->setDateAdded(new \DateTime("now"))
                    ->setStatus('unsent');

                $om->persist($orderToLogistics);
                $om->flush();
            }
        }
    }

    /**
     * Send Order to Logistics system
     *
     * @param string $url
     * @param string $xml
     * @return array
     */
    public function sendToLogistics($url, $xml)
    {
        $resp = $this->getCli()->post(
            $url,
            $xml
        );

        if ($resp->headers['Status-Code'] == 200) {
            return array(
                'status' => 'sent',
                'error' => '',
            );
        } else {
            return array(
                'status' => 'error',
                'error' => 'Status code: '.$resp->headers['Status-Code']."\n".'Error:'."\n".$resp->body,
            );
        }
    }

    /**
     * Parse driver assignment in logistics
     * @param string $xml
     * @throws \InvalidArgumentException
     * @return array
     */
    public function parseDriverAssignXml($xml)
    {
        if (empty($xml)) {
            throw new \InvalidArgumentException('No xml given');
        }

        $driverData = array();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $orderElements = $dom->getElementsByTagName('RouteAssigned');

        // Kolkas tik vienas. Jei po kelis nores perduot - reiksapglebt gaubianciu tagu, kitaip nevalidu
        foreach ($orderElements as $order)
        {
            $driverData[] = array(
                'order_id' => $order->getElementsByTagName('Order_id')->item(0)->nodeValue,
                'driver_id' => $order->getElementsByTagName('Driver_id')->item(0)->nodeValue,
                'vehicle_no' => $order->getElementsByTagName('Vehicle_no')->item(0)->nodeValue,
                'planned_delivery_time' => new \DateTime(
                        $order->getElementsByTagName('Planned_delivery_time')->item(0)->nodeValue
                    ),
            );
        }

        return $driverData;
    }

    /**
     * Parse Order status change in logistics
     *
     * @param string $xml
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function parseOrderStatusXml($xml)
    {
        if (empty($xml)) {
            throw new \InvalidArgumentException('No xml given');
        }

        $statusData = array();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $orderStatusElement = $dom->getElementsByTagName('ShipmentStatus');

        // Vienas elementas. Jei po kelis nores perduot - reiksapglebt gaubianciu tagu, kitaip nevalidu
        foreach ($orderStatusElement as $order)
        {
            $failReason = '';
            $failElement = $order->getElementsByTagName('FailReason');

            if (!empty($failElement)) {
                $failReason = $failElement->item(0)->nodeValue;
            }

            $statusData[] = array(
                'order_id' => $order->getElementsByTagName('Order_id')->item(0)->nodeValue,
                'event_date' => new \DateTime(
                        $order->getElementsByTagName('Event_Date')->item(0)->nodeValue
                    ),
                'status' => $order->getElementsByTagName('Status')->item(0)->nodeValue,
                'fail_reason' => $failReason,
            );
        }

        return $statusData;
    }



    /**
     * @param string $part
     * @param int $limit
     * @return mixed
     */
    public function findStreet($part, $limit=15)
    {
        // TODO language force panaikint
        $part_stripped = $this->container->get('food.app.utils.language')->removeChars('lt', $part, false);

        $queryBuilder = $this->container->get('doctrine')->getManager()
            ->getRepository('FoodAppBundle:Street')
            ->createQueryBuilder('s')
            ->where('s.street LIKE :street_orig')
            ->orWhere('s.street LIKE :street_stripped')
            ->setParameters(
                array(
                    'street_orig' => '%'.$part.'%',
                    'street_stripped' => '%'.$part_stripped.'%',
                )
            )
            ->orderBy('s.street', 'ASC')
            ->setMaxResults($limit);

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * @param string $city
     * @return string
     */
    public function convertCityForLogTime($city)
    {
        $majorCities = array(
            'Viln' => 'Vilnius',
            'Kaun' => 'Kaunas',
            'Klaip' => 'Klaipėda',
            'Rig' => 'Riga',
            'Ryg' => 'Ryga'
        );

        // All good, no need to worry
        if (in_array($city, $majorCities)) {
            return $city;
        }

        foreach($majorCities as $possiblePart => $cityName) {
            if (strpos($city, $possiblePart) !== false) {
                return $cityName;
            }
        }

        // Sorry, could not help;
        return $city;
    }
}