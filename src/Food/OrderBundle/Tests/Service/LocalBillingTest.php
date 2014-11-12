<?php
namespace Food\OrderBundle\Tests\Service;

use Food\DishesBundle\Entity\Place;
use Food\OrderBundle\Service\LocalBiller;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

class LocalBillingTest extends \PHPUnit_Framework_TestCase {

    public function testSettersGetter()
    {
        $order = $this->getMock('\Food\OrderBundle\Entity\Order');
        $localBiller = new LocalBiller();

        $localBiller->setOrder($order);
        $gotOrder = $localBiller->getOrder();
        $this->assertEquals($order, $gotOrder);

        $localBiller->setLocale('lt');
        $gotLocale = $localBiller->getLocale();
        $this->assertEquals('lt', $gotLocale);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Not implemented yet
     */
    public function testRollback()
    {
        $localBiller = new LocalBiller();

        $localBiller->rollback();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage You gave me someting, but not order
     */
    public function testBillNotOrder()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $localBiller = new LocalBiller();
        $localBiller->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $logger->expects($this->once())
            ->method('alert')
            ->with('--====================================================');

        $localBiller->bill();
    }

    public function testBill()
    {
        $expectedUrl = 'omg/happy/url';
        $orderId = 48;
        $locale = 'lt';
        $orderHash = '54asds45asf4saf54asf';

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $router = $this->getMockBuilder('\Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMock('\Food\OrderBundle\Entity\Order');
        $place = new Place();
        $orderService = $this->getMock('\Food\OrderBundle\Service\OrderService');
        $cartService = $this->getMock('\Food\CartBundle\Service\CartService');

        $container->expects($this->at(0))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(1))
            ->method('get')
            ->with('food.order')
            ->will($this->returnValue($orderService));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.cart')
            ->will($this->returnValue($cartService));

        $container->expects($this->at(3))
            ->method('get')
            ->with('router')
            ->will($this->returnValue($router));

        $logger->expects($this->at(0))
            ->method('alert')
            ->with('--====================================================');

        $orderService->expects($this->once())
            ->method('setOrder')
            ->with($order);

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($orderId));

        $logger->expects($this->at(1))
            ->method('alert')
            ->with('++ Parinktas atsiskaitymas atsiimamt - skipinam bilinga. Uzsakymo ID: '.$orderId);

        $logger->expects($this->at(2))
            ->method('alert')
            ->with('-------------------------------------');

        $orderService->expects($this->once())
            ->method('setPaymentStatus')
            ->with('complete');

        $order->expects($this->once())
            ->method('getPlace')
            ->will($this->returnValue($place));

        $cartService->expects($this->once())
            ->method('clearCart')
            ->with($place);

        $orderService->expects($this->once())
            ->method('informPlace');

        $order->expects($this->once())
            ->method('getOrderHash')
            ->will($this->returnValue($orderHash));

        $router->expects($this->once())
            ->method('generate')
            ->with('food_cart_success', array('orderHash' => $orderHash))
            ->will($this->returnValue($expectedUrl));


        $localBiller = new LocalBiller();
        $localBiller->setContainer($container);
        $localBiller->setOrder($order);
        $localBiller->setLocale($locale);

        $gotUrl = $localBiller->bill();

        $this->assertEquals($expectedUrl, $gotUrl);
    }
}