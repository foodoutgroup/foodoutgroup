<?php
namespace Food\SmsBundle\Tests\Service;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use Doctrine\Tests\Mocks\ConnectionMock;
use \Food\SmsBundle\Service\MessagesService;
use \Food\SmsBundle\Entity\Message;

class MessagesServiceTest extends \PHPUnit_Framework_TestCase {
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

    public function testSettersGetters()
    {
        // TODO fast test. Replace with mocks...
        $messagesService = new MessagesService($this->container, null);
        $testVar = 'a';

        $messagesService->setManager($testVar);
        $testReturn = $messagesService->getManager();

        $this->assertEquals($testVar, $testReturn);

        $messagesService->setContainer($testVar);
        $testReturn = $messagesService->getContainer();

        $this->assertEquals($testVar, $testReturn);

        $messagesService->setMessagingProvider($testVar);
        $testReturn = $messagesService->getMessagingProvider();

        $this->assertEquals($testVar, $testReturn);
    }

    public function testGetMessage()
    {
        $messageId = 5;

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $messageRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $messagesService = new MessagesService($this->container, null);
        $messagesService->setManager($entityManager);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($messageRepository));

        $messageRepository->expects($this->once())
            ->method('find')
            ->with($messageId)
            ->will($this->returnValue($message));

        $returnedMessage = $messagesService->getMessage($messageId);

        $this->assertEquals($message, $returnedMessage);
    }

    public function testGetMessageByExtIdNotFound()
    {
        $messageExtId = 515;

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $messageRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $messagesService = new MessagesService($this->container, null);
        $messagesService->setManager($entityManager);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($messageRepository));

        $messageRepository->expects($this->once())
            ->method('findBy')
            ->with(array('extId' => $messageExtId), null, 1)
            ->will($this->returnValue(false));

        $returnedMessage = $messagesService->getMessageByExtId($messageExtId);

        $this->assertEquals(false, $returnedMessage);
    }

    /**
     * @expectedException \Exception
     */
    public function testGetMessageByExtIdMoreException()
    {
        $messageExtId = 528;
        $returnArray = array(1, 2);

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $messageRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $messagesService = new MessagesService($this->container, null);
        $messagesService->setManager($entityManager);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($messageRepository));

        $messageRepository->expects($this->once())
            ->method('findBy')
            ->with(array('extId' => $messageExtId), null, 1)
            ->will($this->returnValue($returnArray));

        $messagesService->getMessageByExtId($messageExtId);
    }

    public function testGetMessageNoMessage()
    {
        $messageId = 5;

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $messageRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $messagesService = new MessagesService($this->container, null);
        $messagesService->setManager($entityManager);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($messageRepository));

        $messageRepository->expects($this->once())
            ->method('find')
            ->with($messageId)
            ->will($this->returnValue(false));

        $returnedMessage = $messagesService->getMessage($messageId);

        $this->assertEquals(false, $returnedMessage);
    }

    /**
     * @expectedException \Exception
     */
    public function testSaveMessageException()
    {
        $messagesService = new MessagesService($this->container, null);
        $messagesService->saveMessage('nothing :)');
    }

    public function testSaveMessage()
    {
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $messagesService = new MessagesService($this->container, null);
        $messagesService->setManager($entityManager);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($message);

        $entityManager->expects($this->once())
            ->method('flush');

        $messagesService->saveMessage($message);
    }

    public function testGetAccBalance()
    {
        $returnValue = 3.75;
        /**
         * @var \Food\SmsBundle\Service\InfobipProvider $infobipProvider
         */
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('getAccountBalance')
        );
        $messagesService = new MessagesService($this->container, $infobipProvider);

        $infobipProvider->expects($this->once())
            ->method('getAccountBalance')
            ->will($this->returnValue($returnValue));

        $balance = $messagesService->getAccountBalance();

        $this->assertEquals($returnValue, $balance);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSendWrongFormat()
    {
        $messagesService = new MessagesService($this->container, null);

        $messagesService->sendMessage('aj ne message cia :D');
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUpdateMessagesDeliveryNoData()
    {
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('parseDeliveryReport')
        );
        $messagesService = new MessagesService($this->container, $infobipProvider);

        $messagesService->updateMessagesDelivery('');
    }

    public function testUpdateMessagesDeliveryInfobip()
    {
        /**
         * @var \Food\SmsBundle\Service\InfobipProvider $infobipProvider
         */
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('parseDeliveryReport')
        );

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $messageRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $messagesService = new MessagesService($this->container, $infobipProvider);
        $messagesService->setManager($entityManager);

        $dlrData =
'<DeliveryReport>
 <message id="023120308155716708" sentdate="2010/8/2 14:55:10" donedate="2010/8/2 14:55:16" status="DELIVERED" gsmerror="0" />
</DeliveryReport> ';
        $messageData = array(
            array(
                'extId' => '023120308155716708',
                'sendDate' => '2010-08-02 14:55:10',
                'completeDate' => '2010-08-02 14:55:16',
                'delivered' => true,
                'error' => null,
            )
        );
        $messagesFromDb = array(
            $message
        );

        $infobipProvider->expects($this->once())
            ->method('parseDeliveryReport')
            ->with($dlrData)
            ->will($this->returnValue($messageData));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($messageRepository));

        $messageRepository->expects($this->once())
            ->method('findBy')
            ->with(array('extId' => '023120308155716708'), null, 1)
            ->will($this->returnValue($messagesFromDb));

        $message->expects($this->once())
            ->method('setDelivered')
            ->with($messageData[0]['delivered']);

        $message->expects($this->once())
            ->method('setReceivedAt')
            ->with(new \DateTime($messageData[0]['completeDate']))
            ->will($this->returnValue($message));

        $message->expects($this->once())
            ->method('setLastSendingError')
            ->with($messageData[0]['error'])
            ->will($this->returnValue($message));

        $message->expects($this->once())
            ->method('setLastErrorDate')
            ->with(null);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($message);

        $entityManager->expects($this->once())
            ->method('flush');

        $messagesService->updateMessagesDelivery($dlrData);
    }

    /**
     * @depends testUpdateMessagesDeliveryInfobip
     */
    public function testUpdateMessagesDeliveryInfobipFailed()
    {
        /**
         * @var \Food\SmsBundle\Service\InfobipProvider $infobipProvider
         */
        $infobipProvider = $this->getMock(
            '\Food\SmsBundle\Service\InfobipProvider',
            array('parseDeliveryReport')
        );

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->getMockBuilder('\Food\SmsBundle\Entity\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $messageRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $messagesService = new MessagesService($this->container, $infobipProvider);
        $messagesService->setManager($entityManager);

        $dlrData =
'<DeliveryReport>
 <message id="023120308155716816" sentdate="2013/10/5 14:55:10" donedate="2013/10/05 14:55:16" status="ROUTE_NOT_AVAILABLE" gsmerror="0" />
</DeliveryReport> ';
        $messageData = array(
            array(
                'extId' => '023120308155716816',
                'sendDate' => '2013-10-05 14:55:10',
                'completeDate' => '2013-10-05 14:55:16',
                'delivered' => false,
                'error' => 'ROUTE_NOT_AVAILABLE',
            )
        );
        $messagesFromDb = array(
            $message
        );

        $infobipProvider->expects($this->once())
            ->method('parseDeliveryReport')
            ->with($dlrData)
            ->will($this->returnValue($messageData));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($messageRepository));

        $messageRepository->expects($this->once())
            ->method('findBy')
            ->with(array('extId' => '023120308155716816'), null, 1)
            ->will($this->returnValue($messagesFromDb));

        $message->expects($this->exactly(2))
            ->method('setDelivered')
            ->with($messageData[0]['delivered'])
            ->will($this->returnValue($message));

        $message->expects($this->once())
            ->method('setLastSendingError')
            ->with($messageData[0]['error'])
            ->will($this->returnValue($message));

        $message->expects($this->once())
            ->method('setLastErrorDate')
            ->with(new \DateTime($messageData[0]['completeDate']));

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($message);

        $entityManager->expects($this->once())
            ->method('flush');

        $messagesService->updateMessagesDelivery($dlrData);
    }
}