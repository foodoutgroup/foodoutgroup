<?php
namespace Food\OrderBundle\Tests\Entity;

use Food\OrderBundle\Entity\OrderToLogistics;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

class OrderToLogisticsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown OrderToLogistic status: dont know
     */
    public function testSetStatusException()
    {
        $orderToLogistics = new OrderToLogistics();

        $orderToLogistics->setStatus('dont know');
    }

    public function testMarkStatuses()
    {
        $expectedStatus1 = 'error';
        $expectedStatus2 = 'sent';
        $expectedStatus3 = 'unsent';

        $orderToLogistics = new OrderToLogistics();

        $orderToLogistics->markError();
        $actualStatus1 = $orderToLogistics->getStatus();
        $this->assertEquals($expectedStatus1, $actualStatus1);

        $orderToLogistics->markSent();
        $actualStatus2 = $orderToLogistics->getStatus();
        $this->assertEquals($expectedStatus2, $actualStatus2);

        $orderToLogistics->markUnsent();
        $actualStatus3 = $orderToLogistics->getStatus();
        $this->assertEquals($expectedStatus3, $actualStatus3);

    }
}