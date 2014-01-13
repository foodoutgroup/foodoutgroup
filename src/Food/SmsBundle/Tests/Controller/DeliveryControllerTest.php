<?php

namespace Food\SmsBundle\Tests\Controller;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use Food\SmsBundle\Controller\DeliveryController;
use Symfony\Component\BrowserKit\Request;

class DeliveryControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\HttpKernel\AppKernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @return null
     */
    public function setUp()
    {
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();

        parent::setUp();
    }

    /**
     * @return null
     */
    public function tearDown()
    {
        $this->kernel->shutdown();

        parent::tearDown();
    }

    public function testIndex()
    {
        $dlrData =
'<DeliveryReport>
 <message id="023120308155716708" sentdate="2010/8/2 14:55:10" donedate="2010/8/2 14:55:16" status="DELIVERED" gsmerror="0" />
</DeliveryReport>';
        $request = new Request('/messaging-delivery/', 'POST', array(), array(), array(), array(), $dlrData);

        $messagingService = $this->getMockBuilder('\Food\SmsBundle\Service\MessagesService')
            ->disableOriginalConstructor()
            ->getMock();
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('setDebugEnabled', 'setLogger')
        );

        $controller = new DeliveryController();
        $controller->setContainer($this->container);
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

        $controller->indexAction($request);
    }
}
