<?php
namespace Food\ApiBundle\Tests\Service;

use Food\ApiBundle\Service\OrderService;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

class OrderServiceTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown status:
     */
    public function testStatusConvertEmptyStatus()
    {
        $orderService = new OrderService();
        $orderService->convertOrderStatus('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown status: omg_what_a_status
     */
    public function testStatusConvertInvalidStatus()
    {
        $orderService = new OrderService();
        $orderService->convertOrderStatus('omg_what_a_status');
    }

    public function testStatusConvert()
    {
        $orderService = new OrderService();

        $expectedStatus1 = 'accepted';
        $expectedStatus2 = 'preparing';
        $expectedStatus3 = 'delayed';
        $expectedStatus4 = 'completed';
        $expectedStatus5 = 'failed';
        $expectedStatus6 = 'finished';
        $expectedStatus7 = 'canceled';

        $orderStatus1 = 'new';
        $orderStatus2 = 'accepted';
        $orderStatus3 = 'assigned';
        $orderStatus4 = 'forwarded';
        $orderStatus5 = 'delayed';
        $orderStatus6 = 'completed';
        $orderStatus7 = 'failed';
        $orderStatus8 = 'finished';
        $orderStatus9 = 'canceled';

        $gotStatus1 = $orderService->convertOrderStatus($orderStatus1);
        $gotStatus2 = $orderService->convertOrderStatus($orderStatus2);
        $gotStatus3 = $orderService->convertOrderStatus($orderStatus3);
        $gotStatus4 = $orderService->convertOrderStatus($orderStatus4);
        $gotStatus5 = $orderService->convertOrderStatus($orderStatus5);
        $gotStatus6 = $orderService->convertOrderStatus($orderStatus6);
        $gotStatus7 = $orderService->convertOrderStatus($orderStatus7);
        $gotStatus8 = $orderService->convertOrderStatus($orderStatus8);
        $gotStatus9 = $orderService->convertOrderStatus($orderStatus9);

        $this->assertEquals($expectedStatus1, $gotStatus1);
        $this->assertEquals($expectedStatus2, $gotStatus2);
        $this->assertEquals($expectedStatus2, $gotStatus3);
        $this->assertEquals($expectedStatus2, $gotStatus4);
        $this->assertEquals($expectedStatus3, $gotStatus5);
        $this->assertEquals($expectedStatus4, $gotStatus6);
        $this->assertEquals($expectedStatus5, $gotStatus7);
        $this->assertEquals($expectedStatus6, $gotStatus8);
        $this->assertEquals($expectedStatus7, $gotStatus9);
    }
}