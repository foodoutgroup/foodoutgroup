<?php
namespace Food\SmsBundle\Tests\Service;

use \Food\SmsBundle\Service\InfobipProvider;

class InfobipProviderTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAuthentification()
    {
        $infobipProvider = new InfobipProvider();
        $infobipProvider->authenticate('skanu', '');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAccountBalanceException1()
    {
        $infobipProvider = new InfobipProvider();
        $infobipProvider->authenticate('skanu1', '119279');
        $infobipProvider->getAccountBalance();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAccountBalanceException2()
    {
        $infobipProvider = new InfobipProvider('', 'http://api2.infobip.com/api');
        $infobipProvider->getAccountBalance();
    }

    /**
     * Cia integration testas..
     * TODO - jo cia netures buti, naudosiu gamybai.. Ji iskelsime i serviso testa, kur mockinsim providerio metoda
     */
    public function testAccountBalanceTemp()
    {
        $infobipProvider = new InfobipProvider(null, 'http://api2.infobip.com/api');
        $infobipProvider->authenticate('skanu1', '119279');
        $balance = $infobipProvider->getAccountBalance();

        $this->assertTrue(is_float($balance));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendMessageEmptySender()
    {
        $infobipProvider = new InfobipProvider(null, 'http://api2.infobip.com/api');
        $infobipProvider->sendMessage(null, '+370*****', 'Message');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendMessageEmptyRecipient()
    {
        $infobipProvider = new InfobipProvider(null, 'http://api2.infobip.com/api');
        $infobipProvider->sendMessage('sender.com', null, 'Message');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSendMessageEmptyMessage()
    {
        $infobipProvider = new InfobipProvider(null, 'http://api2.infobip.com/api');
        $infobipProvider->sendMessage('sender.com', '+370*******', null);
    }

    public function testSetApiUrl()
    {
        $infobipProvider = new InfobipProvider();
        $apiUrl = 'http://this.is.api';
        $infobipProvider->setApiUrl($apiUrl);

        $gotApiUrl = $infobipProvider->getApiUrl();

        $this->assertEquals($apiUrl, $gotApiUrl);
    }

    public function testStatusToErrorConversion()
    {
        $infobipProvider = new InfobipProvider();

        $statusCode1 = -11;
        $expectedError1 = 'MISSING_PASSWORD';

        $statusCode2 = -99;
        $expectedError2 = 'GENERAL_ERROR';

        $statusCode3 = -66;
        $expectedError3 = null;

        $returnedError1 = $infobipProvider->getErrorFromStatus($statusCode1);
        $returnedError2 = $infobipProvider->getErrorFromStatus($statusCode2);
        $returnedError3 = $infobipProvider->getErrorFromStatus($statusCode3);

        $this->assertEquals($expectedError1, $returnedError1);
        $this->assertEquals($expectedError2, $returnedError2);
        $this->assertEquals($expectedError3, $returnedError3);
    }

    /**
     * turi neluzti :)
     */
    public function testDebugingOnNoDebugger()
    {
        $infobipProvider = new InfobipProvider();
        $infobipProvider->setLogger(null);
        $infobipProvider->setDebugEnabled(false);
        $gotLogger = $infobipProvider->getLogger();

        $this->assertEquals(null, $gotLogger);

        $infobipProvider->log('Ohh crap, this did not log - no logger');
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
        $infobipProvider = new InfobipProvider();
        $infobipProvider->setLogger($logger);
        $infobipProvider->setDebugEnabled(false);
        $gotLogger = $infobipProvider->getLogger();

        $this->assertEquals($logger, $gotLogger);

        $logger->expects($this->never())
            ->method('debug');

        $infobipProvider->log('Ohh crap, this did not log');
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

        $infobipProvider = new InfobipProvider();
        $infobipProvider->setLogger($logger);
        $infobipProvider->setDebugEnabled(true);
        $gotLogger = $infobipProvider->getLogger();

        $this->assertEquals($logger, $gotLogger);

        $logger->expects($this->once())
            ->method('debug')
            ->with($message);

        $infobipProvider->log($message);
    }

    /**
     * @depends testStatusToErrorConversion
     */
    public function testParse()
    {
        $infobipProvider = new InfobipProvider();

        $infobipResponse1 = '{"results": [{"status":"0","messageid":"072101113352779063","destination":"37061514333"}]}';
        $expectedResult1 = array(
            array(
                'sent' => 1,
                'status' => 0,
                'messageid' => '072101113352779063',
                'error' => null,
                'destination' => '37061514333',
            )
        );

        $infobipResponse2 = '{"results": [{"status":"-1","messageid":"","destination":"000000000000"}]}';
        $expectedResult2 = array(
            array(
                'status' => -1,
                'messageid' => null,
                'destination' => '000000000000',
                'sent' => 0,
                'error' => 'SEND_ERROR',
            )
        );

        $infobipResponse3 = '{"results": [{"status":"0","messageid":"092100115456775780","destination":"385951111111"},{"status":"0","messageid":"092100897063776982","destination":"385952222222"},{"status":"0","messageid":"092105545063777484","destination":"385953333333"}]}';
        $expectedResult3 = array(
            array(
                'status' => 0,
                'messageid' => '092100115456775780',
                'destination' => '385951111111',
                'sent' => 1,
                'error' => null,
            ),
            array(
                'status' => 0,
                'messageid' => '092100897063776982',
                'destination' => '385952222222',
                'sent' => 1,
                'error' => null,
            ),
            array(
                'status' => 0,
                'messageid' => '092105545063777484',
                'destination' => '385953333333',
                'sent' => 1,
                'error' => null,
            ),
        );

        $infobipResponse4 = '{"results": [{"status":"0","messageid":"092100115456775780","destination":"385951111111"},{"status":"-6","messageid":"","destination":"000000000000"},{"status":"0","messageid":"092105545063777484","destination":"385953333333"},{"status":"-1016","messageid":"092105545063777161","destination":"385953333333"}]}';
        $expectedResult4 = array(
            array(
                'status' => 0,
                'messageid' => '092100115456775780',
                'destination' => '385951111111',
                'sent' => 1,
                'error' => null,
            ),
            array(
                'status' => -6,
                'messageid' => null,
                'destination' => '000000000000',
                'sent' => 0,
                'error' => 'MISSING_DESTINATION_ADDRESS',
            ),
            array(
                'status' => 0,
                'messageid' => '092105545063777484',
                'destination' => '385953333333',
                'sent' => 1,
                'error' => null,
            ),
            array(
                'status' => -1016,
                'messageid' => '092105545063777161',
                'destination' => '385953333333',
                'sent' => 0,
                'error' => 'Unknown error returned from InfoBip. Error status: -1016',
            ),
        );

        $parsedResponse = $infobipProvider->parseResponse($infobipResponse1);
        $parsedResponse2 = $infobipProvider->parseResponse($infobipResponse2);
        $parsedResponse3 = $infobipProvider->parseResponse($infobipResponse3);
        $parsedResponse4 = $infobipProvider->parseResponse($infobipResponse4);

        $this->assertEquals($expectedResult1, $parsedResponse);
        $this->assertEquals($expectedResult2, $parsedResponse2);
        $this->assertEquals($expectedResult3, $parsedResponse3);
        $this->assertEquals($expectedResult4, $parsedResponse4);
    }

    /**
     * @depends testParse
     * @expectedException \Food\SmsBundle\Exceptions\ParseException
     */
    public function testParseException()
    {
        $infobipProvider = new InfobipProvider();

        $infobipResponse = '{"results", [{"status":"0","messageid":"072101113352779063","destination":"37061514333"}]}';

        $infobipProvider->parseResponse($infobipResponse);
    }
    /**
     * @depends testParse
     * @expectedException \Food\SmsBundle\Exceptions\ParseException
     */
    public function testParseWrongFormat()
    {
        $infobipProvider = new InfobipProvider();

        $infobipResponse = '{"rezultatas": [{"status":"0","messageid":"072101113352779063","destination":"37061514333"}]}';

        $infobipProvider->parseResponse($infobipResponse);
    }

    public function testDlrRequest()
    {
        $infobipProvider = new InfobipProvider();

        $empptyDlrData =
'<DeliveryReport>
</DeliveryReport>';
        $expectedEmptyData = array();

        $dlrData =
'<DeliveryReport>
 <message id="1023012301" sentdate="2013/7/19 22:0:0" donedate="2013/7/19 22:0:0" status="NOT_SENT" gsmerror="0" />
</DeliveryReport>';
        $expectedData = array(
            array(
                'extId' => '1023012301',
                'sendDate' => '2013-07-19 22:00:00',
                'completeDate' => '2013-07-19 22:00:00',
                'delivered' => false,
                'error' => 'NOT_SENT',
            )
        );

        $dlrData2 =
'<DeliveryReport>
 <message id="1000" sentdate="2010/8/2 14:55:10" donedate="2010/8/2 14:55:16" status="DELIVERED" gsmerror="0" />
</DeliveryReport> ';
        $expectedData2 = array(
            array(
                'extId' => '1000',
                'sendDate' => '2010-08-02 14:55:10',
                'completeDate' => '2010-08-02 14:55:16',
                'delivered' => true,
                'error' => null,
            )
        );

        $dlrData3 =
'<DeliveryReport>
 <message id="1164134301" sentdate="2013/7/22 10:19:0" donedate="2013/7/23 10:19:0" status="NOT_ENOUGH_CREDITS" gsmerror="0" />
 <message id="1023012682" sentdate="2013/12/21 16:12:15" donedate="2013/12/22 16:12:15" status="NOT_DELIVERED" gsmerror="189" />
 <message id="1023079321" sentdate="2013/12/20 23:0:50" donedate="2013/12/20 23:0:50" status="SENT" gsmerror="0" />
</DeliveryReport> ';
        $expectedData3 = array(
            array(
                'extId' => '1164134301',
                'sendDate' => '2013-07-22 10:19:00',
                'completeDate' => '2013-07-23 10:19:00',
                'delivered' => false,
                'error' => 'NOT_ENOUGH_CREDITS',
            ),
            array(
                'extId' => '1023012682',
                'sendDate' => '2013-12-21 16:12:15',
                'completeDate' => '2013-12-22 16:12:15',
                'delivered' => false,
                'error' => 'NOT_DELIVERED GSM Error code: 189',
            ),
            array(
                'extId' => '1023079321',
                'sendDate' => '2013-12-20 23:00:50',
                'completeDate' => '2013-12-20 23:00:50',
                'delivered' => true,
                'error' => null,
            ),
        );

        $dlrData4 =
            '<DeliveryReport>
             <message id="1023012301" sentdate="2013/7/19 22:0:0" donedate="2013/7/19 22:0:0" status="NOT_SENT_YOU_FOOL" gsmerror="0" />
            </DeliveryReport>';
        $expectedData4 = array(
            array(
                'extId' => '1023012301',
                'sendDate' => '2013-07-19 22:00:00',
                'completeDate' => '2013-07-19 22:00:00',
                'delivered' => false,
                'error' => 'Infobip returned unknown status: NOT_SENT_YOU_FOOL',
            )
        );

        $parsedEmptyData = $infobipProvider->parseDeliveryReport($empptyDlrData);
        $parsedData = $infobipProvider->parseDeliveryReport($dlrData);
        $parsedData2 = $infobipProvider->parseDeliveryReport($dlrData2);
        $parsedData3 = $infobipProvider->parseDeliveryReport($dlrData3);
        $parsedData4 = $infobipProvider->parseDeliveryReport($dlrData4);

        $this->assertEquals($expectedEmptyData, $parsedEmptyData);
        $this->assertEquals($expectedData, $parsedData);
        $this->assertEquals($expectedData2, $parsedData2);
        $this->assertEquals($expectedData3, $parsedData3);
        $this->assertEquals($expectedData4, $parsedData4);
    }
}