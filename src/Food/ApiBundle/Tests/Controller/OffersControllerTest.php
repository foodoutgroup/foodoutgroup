<?php

namespace Food\ApiBundle\Tests\Controller;

use Food\AppBundle\Test\WebTestCase;
use Food\PlacesBundle\Entity\BestOffer;

class OffersControllerTest extends WebTestCase
{
    private $place1Id = null;
    private $place2Id = null;

    public function setUp()
    {
        parent::setUp();

        // Create best offers for testing pusposes
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $place1 = $this->getPlace('best_offer_place1');
        $place2 = $this->getPlace('best_offer_place2');
        $this->place1Id = $place1->getId();
        $this->place2Id = $place2->getId();

        $firstOffer = new BestOffer();
        $firstOffer->setTitle('FirstOffer')
            ->setCity('Vilnius')
            ->setActive(true)
            ->setLink('ab1')
            ->setText('firstOfferText')
            ->setPlace($place1);

        $em->persist($firstOffer);
        $em->flush();

        $secondOffer = new BestOffer();
        $secondOffer->setTitle('SecondOffer')
            ->setCity('Kaunas')
            ->setActive(true)
            ->setLink('ab2')
            ->setText('seccondOfferText')
            ->setPlace($place2);

        $em->persist($secondOffer);
        $em->flush();

        $thirdOffer = new BestOffer();
        $thirdOffer->setTitle('thirdOffer')
            ->setCity('Vilnius')
            ->setActive(false)
            ->setLink('ab3')
            ->setText('thirdOfferText')
            ->setPlace($place1);

        $em->persist($thirdOffer);
        $em->flush();
    }

    public function testGetAllOffers()
    {
        $this->client->request(
            'GET',
            '/api/v1/offers/'
        );

        $expectedOffers = array(
            array(
                'title' => 'FirstOffer',
                'city' => 'Vilnius',
                'place' => $this->place1Id,
                'active' => true,
                'text' => 'firstOfferText',
                'image' => '',
                'image_type1' => ''
            ),
            array(
                'title' => 'SecondOffer',
                'city' => 'Kaunas',
                'place' => $this->place2Id,
                'active' => true,
                'text' => 'seccondOfferText',
                'image' => '',
                'image_type1' => ''
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\OffersController::getAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $offersData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($expectedOffers, $offersData);
    }

    public function testGetOffersForVilnius()
    {
        $this->client->request(
            'GET',
            '/api/v1/offers/Vilnius'
        );

        $expectedOffers = array(
            array(
                'title' => 'FirstOffer',
                'city' => 'Vilnius',
                'place' => 1,
                'active' => true,
                'text' => 'firstOfferText',
                'image' => '',
                'image_type1' => ''
            ),
            array(
                'title' => 'FirstOffer',
                'city' => 'Vilnius',
                'place' => $this->place1Id,
                'active' => true,
                'text' => 'firstOfferText',
                'image' => '',
                'image_type1' => ''
            ),
        );

        $this->assertEquals('Food\ApiBundle\Controller\OffersController::getAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $offersData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($expectedOffers, $offersData);
    }
}