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
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBillNotOrder()
    {
        $this->markTestIncomplete();
        $paysera = new PaySera();

        $paysera->bill();
    }

    public function testBill()
    {
        $this->markTestIncomplete();
        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $order = $this->getMock('\Food\OrderBundle\Entity\Order');

        $evpService = $this->getMock('\WebToPay', array('redirectPayment'));

        $evpParameters = array(
            'projectid' => 1545,
            'sign_password' => 'omgSoSecure',
            'orderid' => 48,
            'amount' => 115.55,
            'currency' => 'LTL',
            'country' => 'LT',
            'accepturl' => 'skanu.lv/webtopay/accept/',
            'cancelurl' => 'skanu.lv/webtopay/cancel/',
            'callbackurl' => 'skanu.lv/webtopay/callback/',
            'test' => 0,
        );

        $paysera = new PaySera();
        $paysera->setContainer($container);
        $paysera->setOrder($order);
        $paysera->setSightPassword('omgSoSecure');
        $paysera->setSiteDomain('skanu.lv');
        $paysera->setProjectId(1545);

        $order->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(48));

        $order->expects($this->once())
            ->method('getTotal')
            ->will($this->returnValue(115.55));

        $container->expects($this->once())
            ->method('get')
            ->with('evp_web_to_pay.request_builder')
            ->will($this->returnValue($evpService));

        $evpService->expects($this->once())
            ->method('redirectPayment')
            ->with($evpParameters);

        $paysera->bill();
    }
}