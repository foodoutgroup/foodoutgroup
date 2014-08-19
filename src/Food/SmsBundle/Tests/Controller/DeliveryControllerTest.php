<?php

namespace Food\SmsBundle\Tests\Controller;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use Food\SmsBundle\Controller\DeliveryController;
use Symfony\Component\HttpFoundation\Request;

class DeliveryControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testIndex()
    {
        $dlrData =
'<DeliveryReport>
 <message id="023120308155716708" sentdate="2010/8/2 14:55:10" donedate="2010/8/2 14:55:16" status="DELIVERED" gsmerror="0" />
</DeliveryReport>';
        $request = new Request(array(), array(), array(), array(), array(), array(), $dlrData);

        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled', 'setLogger')
        );
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );

        $controller = new DeliveryController();
        $controller->setContainer($container);
        $controller->setMessagingService($messagingService);
        $controller->setProvider($infobipProvider);

        $infobipProvider->expects($this->once())
            ->method('setLogger');

        $infobipProvider->expects($this->once())
            ->method('setDebugEnabled')
            ->with(true);

        $messagingService->expects($this->once())
            ->method('setMessagingProvider')
            ->with($infobipProvider);

        $messagingService->expects($this->once())
            ->method('updateMessagesDelivery');

        $controller->indexAction('infobip', $request);
    }
}
