<?php
namespace Food\SmsBundle\Tests\Service;

use Food\SmsBundle\Service\SilverStreetProvider;

class SilverstreetProviderTest extends \PHPUnit_Framework_TestCase {

    public function testSettersGetters()
    {
        $silverstreetProvider = new SilverStreetProvider();

        $curl = new \Curl();

        $curl2 = new \Curl();
        $curl2->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl2->options['CURLOPT_SSL_VERIFYHOST'] = false;

        $silverstreetProvider->setCli($curl);
        $gotCurl = $silverstreetProvider->getCli();

        $this->assertEquals($curl, $gotCurl);

        $silverstreetProvider->setCli(null);
        $gotCurl2 = $silverstreetProvider->getCli();
        $this->assertEquals($curl2, $gotCurl2);

        $this->assertEquals(
            'Silverstreet',
            $silverstreetProvider->getProviderName()
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAuthentification()
    {
        $silverstreetProvider = new SilverStreetProvider();
        $silverstreetProvider->authenticate('skanu', '');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAccountBalanceException1()
    {
        $silverstreetProvider = new SilverStreetProvider();
        $silverstreetProvider->authenticate('skanu1', '119279');
        $silverstreetProvider->getAccountBalance();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAccountBalanceException2()
    {
        $silverstreetProvider = new SilverStreetProvider('', 'http://api.silverstreet.com/creditcheck.php');
        $silverstreetProvider->getAccountBalance();
    }
//
//    /**
//     * Cia integration testas..
//     * TODO - jo cia netures buti, naudosiu gamybai.. Ji iskelsime i serviso testa, kur mockinsim providerio metoda
//     */
//    public function testAccountBalanceTemp()
//    {
//        $infobipProvider = new InfobipProvider(null, 'http://api2.infobip.com/api');
//        $infobipProvider->authenticate('skanu1', '119279');
//        $balance = $infobipProvider->getAccountBalance();
//
//        $this->assertTrue(is_float($balance));
//    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendMessageEmptySender()
    {
        $silverstreetProvider = new SilverStreetProvider(null, 'http://api.silverstreet.com/send.php');
        $silverstreetProvider->sendMessage(null, '+370*****', 'Message');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendMessageEmptyRecipient()
    {
        $silverstreetProvider = new SilverStreetProvider(null, 'http://api.silverstreet.com/send.php');
        $silverstreetProvider->sendMessage('sender.com', null, 'Message');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendMessageEmptyMessage()
    {
        $silverstreetProvider = new SilverStreetProvider(null, 'http://api.silverstreet.com/send.php');
        $silverstreetProvider->sendMessage('sender.com', '+370*******', null);
    }

    public function testSetApiUrl()
    {
        $silverstreetProvider = new SilverStreetProvider();
        $apiUrl = 'http://this.is.api';
        $silverstreetProvider->setApiUrl($apiUrl);

        $gotApiUrl = $silverstreetProvider->getApiUrl();

        $this->assertEquals($apiUrl, $gotApiUrl);
    }

    public function testStatusToErrorConversion()
    {
        $silverstreetProvider = new SilverStreetProvider();

        $statusCode1 = 100;
        $expectedError1 = 'PARAMETERS_MISSING';

        $statusCode2 = 110;
        $expectedError2 = 'BAD_PARAMETERS_COMBINATION';

        $statusCode3 = 120;
        $expectedError3 = 'INVALID_PARAMETERS';

        $statusCode4= 130;
        $expectedError4 = 'INSUFFICIENT_CREDITS';

        $statusCode5 = 150;
        $expectedError5 = null;

        $returnedError1 = $silverstreetProvider->getErrorFromStatus($statusCode1);
        $returnedError2 = $silverstreetProvider->getErrorFromStatus($statusCode2);
        $returnedError3 = $silverstreetProvider->getErrorFromStatus($statusCode3);
        $returnedError4 = $silverstreetProvider->getErrorFromStatus($statusCode4);
        $returnedError5 = $silverstreetProvider->getErrorFromStatus($statusCode5);

        $this->assertEquals($expectedError1, $returnedError1);
        $this->assertEquals($expectedError2, $returnedError2);
        $this->assertEquals($expectedError3, $returnedError3);
        $this->assertEquals($expectedError4, $returnedError4);
        $this->assertEquals($expectedError5, $returnedError5);
    }

    /**
     * turi neluzti :)
     */
    public function testDebugingOnNoDebugger()
    {
        $silverstreetProvider = new SilverStreetProvider();
        $silverstreetProvider->setLogger(null);
        $silverstreetProvider->setDebugEnabled(false);
        $gotLogger = $silverstreetProvider->getLogger();

        $this->assertEquals(null, $gotLogger);

        $silverstreetProvider->log('Ohh crap, this did not log - no logger');
    }

    /**
     * @depends testDebugingOnNoDebugger
     */
    public function testDebugingOff()
    {
        $logger = $this->getMock(
            '\Monolog\Logger',
            array('debug'),
            array('test.log')
        );
        $silverstreetProvider = new SilverStreetProvider();
        $silverstreetProvider->setLogger($logger);
        $silverstreetProvider->setDebugEnabled(false);
        $gotLogger = $silverstreetProvider->getLogger();

        $this->assertEquals($logger, $gotLogger);

        $logger->expects($this->never())
            ->method('debug');

        $silverstreetProvider->log('Ohh crap, this did not log');
    }

    /**
     * @depends testDebugingOnNoDebugger
     */
    public function testDebugingOn()
    {
        $logger = $this->getMock(
            '\Monolog\Logger',
            array('debug'),
            array('test.log')
        );
        $message = 'Magic message';

        $silverstreetProvider = new SilverStreetProvider();
        $silverstreetProvider->setLogger($logger);
        $silverstreetProvider->setDebugEnabled(true);
        $gotLogger = $silverstreetProvider->getLogger();

        $this->assertEquals($logger, $gotLogger);

        $logger->expects($this->once())
            ->method('debug')
            ->with($message);

        $silverstreetProvider->log($message);
    }

    /**
     * @depends testStatusToErrorConversion
     */
    public function testParse()
    {
        $silverstreetProvider = new SilverStreetProvider();

        $silverstreetResponse1 = '1';
        $expectedResult1 = array(
            'sent' => 1,
            'error' => null,
        );

        $silverstreetResponse2 = '100';
        $expectedResult2 = array(
            'sent' => 0,
            'error' => 'PARAMETERS_MISSING',
        );

        $silverstreetResponse3 = '110';
        $expectedResult3 = array(
            'sent' => 0,
            'error' => 'BAD_PARAMETERS_COMBINATION',
        );

        $silverstreetResponse4 = '160';
        $expectedResult4 = array(
            'sent' => 0,
            'error' => 'Unknown error returned from Silverstreet. Error status: 160',
        );

        $parsedResponse = $silverstreetProvider->parseResponse($silverstreetResponse1);
        $parsedResponse2 = $silverstreetProvider->parseResponse($silverstreetResponse2);
        $parsedResponse3 = $silverstreetProvider->parseResponse($silverstreetResponse3);
        $parsedResponse4 = $silverstreetProvider->parseResponse($silverstreetResponse4);

        $this->assertEquals($expectedResult1, $parsedResponse);
        $this->assertEquals($expectedResult2, $parsedResponse2);
        $this->assertEquals($expectedResult3, $parsedResponse3);
        $this->assertEquals($expectedResult4, $parsedResponse4);
    }

    public function testDlrRequest()
    {
        $silverstreetProvider = new SilverStreetProvider();

        $empptyDlrData = array();
        $expectedEmptyData = array(array());

        $dlrData = array(
            'reference' => '1023012301',
            'status' => 'Not Delivered',
            'reason' => '',
            'destination' => '37061514333',
            'timestamp' => '20130719230000',
            'operator' => '3'
        );
        $expectedData = array(
            array(
                'extId' => '1023012301',
                'sendDate' => null,
                'completeDate' => '2013-07-20 03:00:00',
                'delivered' => false,
                'error' => 'Silverstreet undelivered due to: no reason',
            )
        );

        $dlrData2 = array(
            'reference' => 'sil105242323',
            'status' => 'Buffered',
            'reason' => '',
            'destination' => '37061514333',
            'timestamp' => '20100802145516',
            'operator' => '3'
        );
        $expectedData2 = array(
            array(
                'extId' => 'sil105242323',
                'sendDate' => null,
                'completeDate' => '2010-08-02 18:55:16',
                'delivered' => false,
                'error' => 'Silverstreet undelivered due to: no reason',
            )
        );

        $dlrData3 = array(
            'reference' => '10230154701',
            'status' => 'Delivered',
            'reason' => '',
            'destination' => '37061514333',
            'timestamp' => '20130719230000',
            'operator' => '3'
        );
        $expectedData3 = array(
            array(
                'extId' => '10230154701',
                'sendDate' => null,
                'completeDate' => '2013-07-20 03:00:00',
                'delivered' => true,
                'error' => '',
            )
        );

        $dlrData4 = array(
            'reference' => '10230154701',
            'status' => 'So undelivered',
            'reason' => '',
            'destination' => '37061514333',
            'timestamp' => '20130717210050',
            'operator' => '3'
        );
        $expectedData4 = array(
            array(
                'extId' => '10230154701',
                'sendDate' => null,
                'completeDate' => '2013-07-18 01:00:50',
                'delivered' => false,
                'error' => 'Silverstreet returned unknown status: So undelivered',
            )
        );

        $dlrData5 = array(
            'reference' => '10230154701',
            'status' => 'Not Delivered',
            'reason' => '58',
            'destination' => '37061514333',
            'timestamp' => '20130717210050',
            'operator' => '3'
        );
        $expectedData5 = array(
            array(
                'extId' => '10230154701',
                'sendDate' => null,
                'completeDate' => '2013-07-18 01:00:50',
                'delivered' => false,
                'error' => 'Silverstreet undelivered due to: Rejected due to flooding',
            )
        );

        $dlrData6 = array(
            'reference' => '10230154801',
            'status' => 'Not Delivered',
            'reason' => '100',
            'destination' => '37061514333',
            'timestamp' => '20130717110050',
            'operator' => '3'
        );
        $expectedData6 = array(
            array(
                'extId' => '10230154801',
                'sendDate' => null,
                'completeDate' => '2013-07-17 15:00:50',
                'delivered' => false,
                'error' => 'Silverstreet undelivered due to: unknown reason code: 100',
            )
        );

        $parsedEmptyData = $silverstreetProvider->parseDeliveryReport($empptyDlrData);
        $parsedData = $silverstreetProvider->parseDeliveryReport($dlrData);
        $parsedData2 = $silverstreetProvider->parseDeliveryReport($dlrData2);
        $parsedData3 = $silverstreetProvider->parseDeliveryReport($dlrData3);
        $parsedData4 = $silverstreetProvider->parseDeliveryReport($dlrData4);
        $parsedData5 = $silverstreetProvider->parseDeliveryReport($dlrData5);
        $parsedData6 = $silverstreetProvider->parseDeliveryReport($dlrData6);

        $this->assertEquals($expectedEmptyData, $parsedEmptyData);
        $this->assertEquals($expectedData, $parsedData);
        $this->assertEquals($expectedData2, $parsedData2);
        $this->assertEquals($expectedData3, $parsedData3);
        $this->assertEquals($expectedData4, $parsedData4);
        $this->assertEquals($expectedData5, $parsedData5);
        $this->assertEquals($expectedData6, $parsedData6);
    }
}