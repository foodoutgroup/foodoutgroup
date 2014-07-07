<?php

namespace Food\AppBundle\Tests\Utils;

use Food\AppBundle\Entity\BannedIp;
use Food\AppBundle\Utils\Misc;

class MiscTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetContainers()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $util = new Misc();

        $util->setContainer($container);
        $gotContainer = $util->getContainer();
        $this->assertEquals($container, $gotContainer);
    }

    public function testFormatPhone()
    {
        $util = new Misc();

        $phoneToFormat1 = '37061514333';
        $phoneToFormat2 = '861514333';
        $phoneToFormat3 = '+37061514333';
        $phoneToFormat4 = '0037061514333';

        $expectedResult = '37061514333';

        $formatedPhone1 = $util->formatPhone($phoneToFormat1, 'LT');
        $formatedPhone2 = $util->formatPhone($phoneToFormat2, 'LT');
        $formatedPhone3 = $util->formatPhone($phoneToFormat3, 'LT');
        $formatedPhone4 = $util->formatPhone($phoneToFormat4, 'LT');
        $formatedPhone5 = $util->formatPhone('00', 'LT');

        $this->assertEquals($expectedResult, $formatedPhone1);
        $this->assertEquals($expectedResult, $formatedPhone2);
        $this->assertEquals($expectedResult, $formatedPhone3);
        $this->assertEquals($expectedResult, $formatedPhone4);
        $this->assertEquals(null, $formatedPhone5);
    }

    public function testIpBanned()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $ipRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $util = new Misc();
        $util->setContainer($container);

        $ip = '127.0.0.1';
        $isBannedExpected = true;

        $ipEntity = new BannedIp();
        $ipEntity->setActive(true)
            ->setIp('127.0.0.1')
            ->setReason('test');

        $findParams = array('ip' => $ip, 'active' => true);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with('FoodAppBundle:BannedIp')
            ->will($this->returnValue($ipRepository));

        $ipRepository->expects($this->once())
            ->method('findOneBy')
            ->with($findParams)
            ->will($this->returnValue($ipEntity));

        $isBanned = $util->isIpBanned($ip);

        $this->assertEquals($isBannedExpected, $isBanned);
    }

    public function testIpBannedFalse()
    {
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $ipRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $util = new Misc();
        $util->setContainer($container);

        $ip = '127.0.0.1';
        $isBannedExpected = false;

        $findParams = array('ip' => $ip, 'active' => true);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getRepository')
            ->with('FoodAppBundle:BannedIp')
            ->will($this->returnValue($ipRepository));

        $ipRepository->expects($this->once())
            ->method('findOneBy')
            ->with($findParams)
            ->will($this->returnValue(false));

        $isBanned = $util->isIpBanned($ip);

        $this->assertEquals($isBannedExpected, $isBanned);
    }
}
