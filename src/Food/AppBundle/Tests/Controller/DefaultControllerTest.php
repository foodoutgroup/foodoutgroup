<?php

namespace Food\AppBundle\Tests\Controller;

use Food\AppBundle\Entity\BannedIp;
use Food\AppBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testBanInAction()
    {
        $em = $this->getDoctrine()->getManager();
        $client = $this->client;
        $client->followRedirects();

        $ipBan = new BannedIp();
        $ipBan->setActive(true)
            ->setCreatedAt(new \DateTime("now"))
            ->setIp('127.0.0.1')
            ->setReason('test purpose');
        $em->persist($ipBan);
        $em->flush();

        // Test ban in action
        $crawler = $client->request('GET', '/lt/');
        $this->assertTrue(
            $crawler->filter('html:contains("test purpose")')->count() > 0
        );

        // Test ban in action for diferent url
        $crawler = $client->request('GET', '/lt/pagalba/');
        $this->assertTrue(
            $crawler->filter('html:contains("test purpose")')->count() > 0
        );

        // Test - ban is lifted
        $em->remove($ipBan);
        $em->flush();

        $crawler = $client->request('GET', '/lt/');

        $this->assertTrue(
            $crawler->filter('html:contains("test purpose")')->count() == 0
        );
    }
}
