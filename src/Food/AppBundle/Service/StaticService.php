<?php
namespace Food\AppBundle\Service;

use Doctrine\Tests\Common\Annotations\Ticket\Doctrine\ORM\Mapping\Entity;

class StaticService {

    /**
     * @var Container
     */
    private $container;

    /**
     * @var int
     */
    private $userId;

    /**
     * @param Container $container
     * @param $userId
     */
    public function __construct($container, $userId)
    {
        $this->container = $container;
        $this->userId = $userId;
    }

    /**
     * @param \Food\AppBundle\Service\Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Food\AppBundle\Service\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * TODO
     * @param $id
     * @throws \InvalidArgumentException
     */
    public function getPage($id)
    {
        throw new \InvalidArgumentException('Sorry, no ID - no information. Get lucky!');
    }

    /**
     * TODO
     *
     * @param $slug
     */
    public function getPageBySlug($slug)
    {
        // TODO
    }
}