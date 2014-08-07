<?php

namespace Food\ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImagesControllerTest extends WebTestCase
{
    public function testImageNotFound()
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/api/v1/images/',
            array(
                'imagename' => 'zis_for_shor_not_exist.jpg',
                'size' => 234,
                'box' => false,
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\ImagesController::imageAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(404 , $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filter('html:contains("The image was not found")')->count());
    }

    public function testImageNoSize()
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/api/v1/images/',
            array(
                'imagename' => '/bundles/foodapp/images/logo_food.png',
                'size' => null,
                'box' => false,
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\ImagesController::imageAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(500 , $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filter('html:contains("an internal error occured")')->count());
    }
}