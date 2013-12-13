<?php
namespace Food\SmsBundle\Tests\Service;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use \Food\SmsBundle\Service\MessagesService;
use \Food\SmsBundle\Entity\Message;

class MessagesServiceTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var \Symfony\Component\HttpKernel\AppKernel
     */
    protected $kernel;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

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
        $this->entityManager = $this->container->get('doctrine')->getManager();

//        $this->generateSchema();

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

    public function testInfobipSend()
    {
        /**
         * @var \Food\SmsBundle\Service\InfobipProvider $infobipProvider
         */
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('call', 'setApiUrl', 'authenticate', 'parseResponse')
        );
        $messagesService = new MessagesService($this->container, $infobipProvider);

        $message = new Message();
        $message->setSender('skanu')
            ->setRecipient('37061514333')
            ->setMessage('Pica iskepe')
            ->setCreatedAt(new \DateTime("now"))
        ;

        $expectedCallValue = array(
            array(
                'sender' => 'skanu',
                'text' => 'Pica iskepe',
                'recipients' => array(
                    array('gsm' => 37061514333),
                )
            )
        );

        $fakeInfobipResponse = '{"results": [{"status":"0","messageid":"072101113352779063","destination":"385951111111"}]}';

        $expectedParseReturnValue = array(
            'sent' => true,
            'status' => 8,
            'messageid' => 123456,
            'error' => null,
            'destination' => '37061514333',
        );

        $infobipProvider->expects($this->once())
            ->method('call')
            ->with($this->equalTo($expectedCallValue))
            ->will($this->returnValue($fakeInfobipResponse));

        $infobipProvider->expects($this->once())
            ->method('parseResponse')
            ->with($this->equalTo($fakeInfobipResponse))
            ->will($this->returnValue($expectedParseReturnValue));

        $messagesService->sendMessage($message);
    }
}