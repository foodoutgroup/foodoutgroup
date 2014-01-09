<?php

namespace Food\AppBundle\Tests\Service;

use Food\AppBundle\Service\StaticService;

class StaticServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testSettersGetters()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $userId = 3;

        $staticService = new StaticService($container, $userId);

        $userIdGot = $staticService->getUserId();
        $this->assertEquals($userId, $userIdGot);

        $containerGot1 = $staticService->getContainer();
        $this->assertEquals($container, $containerGot1);

        $staticService->setContainer(null);
        $containerGot2 = $staticService->getContainer();
        $this->assertNull($containerGot2);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetPageNoId()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $userId = 9;

        $staticService = new StaticService($container, $userId);
        $staticService->getPage(null);
    }

    public function testGetPage()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $userId = 3;
        $pageId = 12;

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
        ->disableOriginalConstructor()
        ->getMock();

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $staticContent = $this->getMockBuilder('\Food\AppBundle\Entity\StaticContent')
            ->disableOriginalConstructor()
            ->getMock();

        $staticContentRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $staticService = new StaticService($container, $userId);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($staticContentRepo));

        $staticContentRepo->expects($this->once())
            ->method('find')
            ->with($pageId)
            ->will($this->returnValue($staticContent));

        $pageReturned = $staticService->getPage($pageId);

        $this->assertEquals($staticContent, $pageReturned);
    }

    public function testGetPageNotFound()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $userId = 7;
        $pageId = 16;

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
        ->disableOriginalConstructor()
        ->getMock();

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $staticContentRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $staticService = new StaticService($container, $userId);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($staticContentRepo));

        $staticContentRepo->expects($this->once())
            ->method('find')
            ->with($pageId)
            ->will($this->returnValue(null));

        $pageReturned = $staticService->getPage($pageId);

        $this->assertEquals(false, $pageReturned);
    }

}
