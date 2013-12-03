<?php
namespace Food\SmsBundle\Tests\Service;

//use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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

        $infobipResponse4 = '{"results": [{"status":"0","messageid":"092100115456775780","destination":"385951111111"},{"status":"-6","messageid":"","destination":"000000000000"},{"status":"0","messageid":"092105545063777484","destination":"385953333333"}]}';
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
}