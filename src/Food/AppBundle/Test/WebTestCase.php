<?php 

namespace Food\AppBundle\Test;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;

class WebTestCase extends SymfonyWebTestCase
{

    /**
     * @var Client
     */
    protected $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    protected function getContainer()
    {
        return $this->client->getContainer();
    }

    protected function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}
