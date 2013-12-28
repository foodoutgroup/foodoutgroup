<?php

namespace Food\SmsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeliveryControllerTest extends WebTestCase
{
    public function setUp()
    {
        // reikes setup'o? :)
    }

    /**
     * TODO Finish the test
     */
    public function testIndex()
    {
        $client = static::createClient();

        $dlrData =
'<DeliveryReport>
 <message id="023120308155716708" sentdate="2010/8/2 14:55:10" donedate="2010/8/2 14:55:16" status="DELIVERED" gsmerror="0" />
</DeliveryReport>';

        $crawler = $client->request('POST', '/messaging-delivery/', $dlrData);

//        $this->assertTrue($crawler->filter('html:contains("Hello Fabien")')->count() > 0);
    }
}
