<?php

namespace Food\OrderBundle\Tests\Controller;

use Food\OrderBundle\Controller\DefaultController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    /*public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hello/Fabien');

        $this->assertTrue($crawler->filter('html:contains("Hello Fabien")')->count() > 0);
    }*/

    public function testFormStatusConversion()
    {
        $controller = new DefaultController();

        $formStatus1 = 'confirm';
        $expectedStatus1 = 'accepted';
        $formStatus2 = 'cancel';
        $expectedStatus2 = 'canceled';
        $formStatus3 = 'finish';
        $expectedStatus3 = 'finished';
        $formStatus4 = 'completed';
        $expectedStatus4 = 'completed';
        $formStatus5 = 'compomg_status';
        $expectedStatus5 = '';

        $statusGot1 = $controller->formToEntityStatus($formStatus1);
        $statusGot2 = $controller->formToEntityStatus($formStatus2);
        $statusGot3 = $controller->formToEntityStatus($formStatus3);
        $statusGot4 = $controller->formToEntityStatus($formStatus4);
        $statusGot5 = $controller->formToEntityStatus($formStatus5);

        $this->assertEquals($expectedStatus1, $statusGot1);
        $this->assertEquals($expectedStatus2, $statusGot2);
        $this->assertEquals($expectedStatus3, $statusGot3);
        $this->assertEquals($expectedStatus4, $statusGot4);
        $this->assertEquals($expectedStatus5, $statusGot5);
    }
}
