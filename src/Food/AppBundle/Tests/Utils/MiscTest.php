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

    public function testIsMobilePhone()
    {
        $util = new Misc();

        $phoneToFormat1 = '37061514333';
        $phoneToFormat2 = '861514333';
        $phoneToFormat3 = '+37061514333';
        $phoneToFormat4 = '0037061514333';
        $phoneToFormat5 = '00';
        $phoneToFormat6 = '852440593';
        $phoneToFormat7 = '37052440593';

        $formatedPhone1 = $util->isMobilePhone($phoneToFormat1, 'LT');
        $formatedPhone2 = $util->isMobilePhone($phoneToFormat2, 'LT');
        $formatedPhone3 = $util->isMobilePhone($phoneToFormat3, 'LT');
        $formatedPhone4 = $util->isMobilePhone($phoneToFormat4, 'LT');
        $formatedPhone5 = $util->isMobilePhone($phoneToFormat5, 'LT');
        $formatedPhone6 = $util->isMobilePhone($phoneToFormat6, 'LT');
        $formatedPhone7 = $util->isMobilePhone($phoneToFormat7, 'LT');

        $this->assertTrue($formatedPhone1);
        $this->assertTrue($formatedPhone2);
        $this->assertTrue($formatedPhone3);
        $this->assertTrue($formatedPhone4);
        $this->assertFalse($formatedPhone5);
        $this->assertFalse($formatedPhone6);
        $this->assertFalse($formatedPhone7);
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

    public function testEuro()
    {
        $util = new Misc();

        $expectedPrice1 = 4.34;
        $expectedPrice2 = 10.59;
        $expectedPrice3 = 0;
        $expectedPrice4 = 30.22;
        $expectedPrice5 = 23.35;

        $testPrice1 = 15;
        $testPrice2 = 36.58;
        $testPrice3 = 0;
        $testPrice4 = 104.36;
        $testPrice5 = 80.61;

        $gotPrice1 = $util->getEuro($testPrice1);
        $gotPrice2 = $util->getEuro($testPrice2);
        $gotPrice3 = $util->getEuro($testPrice3);
        $gotPrice4 = $util->getEuro($testPrice4);
        $gotPrice5 = $util->getEuro($testPrice5);

        $this->assertEquals($expectedPrice1, $gotPrice1);
        $this->assertEquals($expectedPrice2, $gotPrice2);
        $this->assertEquals($expectedPrice3, $gotPrice3);
        $this->assertEquals($expectedPrice4, $gotPrice4);
        $this->assertEquals($expectedPrice5, $gotPrice5);
    }
}
