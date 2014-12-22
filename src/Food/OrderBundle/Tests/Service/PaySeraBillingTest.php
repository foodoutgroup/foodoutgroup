<?php
namespace Food\OrderBundle\Tests\Service;

use Food\OrderBundle\Service\PaySera;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

class PaySeraBillingTest extends \PHPUnit_Framework_TestCase {
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

    public function testSettersGetter()
    {
        $order = $this->getMock('\Food\OrderBundle\Entity\Order');
        $payseraBiller = new PaySera();

        $payseraBiller->setProjectId(1789);
        $projectId = $payseraBiller->getProjectId();
        $this->assertEquals(1789, $projectId);

        $payseraBiller->setSiteDomain('supermega.ru');
        $siteUrl = $payseraBiller->getSiteDomain();
        $this->assertEquals('supermega.ru', $siteUrl);

        $payseraBiller->setOrder($order);
        $gotOrder = $payseraBiller->getOrder();
        $this->assertEquals($order, $gotOrder);

        $payseraBiller->setSightPassword('topSecret');
        $password = $payseraBiller->getSightPassword();
        $this->assertEquals('topSecret', $password);

        $isTestDefault = $payseraBiller->getTest();
        $this->assertEquals(0, $isTestDefault);

        $payseraBiller->setTest(1);
        $isTest = $payseraBiller->getTest();
        $this->assertEquals(1, $isTest);

        $payseraBiller->setLocale('lt');
        $gotLocale = $payseraBiller->getLocale();
        $this->assertEquals('lt', $gotLocale);
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

        $paysera = new PaySera();
        $paysera->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $logger->expects($this->once())
            ->method('alert')
            ->with('--====================================================');

        $paysera->bill();
    }

    public function testBill()
    {
        $expectedUrl = 'omg/evp/url';
        $acceptUrl = 'skanu.lv/webtopay/accept/';
        $cancelUrl = 'skanu.lv/webtopay/cancel/';
        $callbackUrl = 'skanu.lv/webtopay/callback/';
        $orderId = 48;
        $orderTotal = 115.55;
        $projectId = 1545;
        $locale = 'lt';
        $orderHash = '54asds45asf4saf54asf';

        $evpParameters = array(
            'projectid' => $projectId,
            'sign_password' => 'omgSoSecure',
            'orderid' => $orderId,
            'amount' => $orderTotal*100,
            'currency' => 'LTL',
            'country' => 'LT',
            'accepturl' => $acceptUrl,
            'cancelurl' => $cancelUrl,
            'callbackurl' => $callbackUrl,
            'test' => 1,
            'time_limit' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        );


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

        $evpService = $this->getMock('\WebToPay', array('buildRequestUrlFromData'));

        $container->expects($this->at(0))
            ->method('get')
            ->with('logger')
            ->will($this->returnValue($logger));

        $container->expects($this->at(1))
            ->method('get')
            ->with('router')
            ->will($this->returnValue($router));

        $logger->expects($this->at(0))
            ->method('alert')
            ->with('--====================================================');

        $logger->expects($this->at(1))
            ->method('alert')
            ->with('++ Bandom bilinti orderi su Id: '.$orderId);

        $logger->expects($this->at(2))
            ->method('alert')
            ->with('-------------------------------------');

        $order->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue($orderId));

        $order->expects($this->once())
            ->method('getOrderHash')
            ->will($this->returnValue($orderHash));

        $order->expects($this->once())
            ->method('getTotal')
            ->will($this->returnValue($orderTotal));

        $router->expects($this->at(0))
            ->method('generate')
            ->with('paysera_accept', array('_locale' => $locale), true)
            ->will($this->returnValue($acceptUrl));

        $router->expects($this->at(1))
            ->method('generate')
            ->with('paysera_cancel',
                array(
                    'hash' => $orderHash,
                    '_locale' => $locale,
                ),
                true)
            ->will($this->returnValue($cancelUrl));

        $router->expects($this->at(2))
            ->method('generate')
            ->with('paysera_callback', array(), true)
            ->will($this->returnValue($callbackUrl));

        $logger->expects($this->at(3))
            ->method('alert')
            ->with('++ EVP paduodami paramsai:');

        $logger->expects($this->at(4))
            ->method('alert')
            ->with(var_export($evpParameters, true));

        $container->expects($this->at(2))
            ->method('get')
            ->with('evp_web_to_pay.request_builder')
            ->will($this->returnValue($evpService));

        $evpService->expects($this->once())
            ->method('buildRequestUrlFromData')
            ->with($evpParameters)
            ->will($this->returnValue($expectedUrl));

        $logger->expects($this->at(5))
            ->method('alert')
            ->with('-------------------------------------');

        $logger->expects($this->at(6))
            ->method('alert')
            ->with('Suformuotas url: '.$expectedUrl);

        $logger->expects($this->at(7))
            ->method('alert')
            ->with('-------------------------------------');


        $paysera = new PaySera();
        $paysera->setContainer($container);
        $paysera->setOrder($order);
        $paysera->setSightPassword('omgSoSecure');
        $paysera->setProjectId($projectId);
        $paysera->setTest(1);
        $paysera->setLocale($locale);

        $gotUrl = $paysera->bill();

        $this->assertEquals($expectedUrl, $gotUrl);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Not implemented yet
     */
    public function testRollback()
    {
        $payseraBiller = new PaySera();

        $payseraBiller->rollback();
    }
}