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

    public function testParseAddress()
    {
        $util = new Misc();

        $expectedResult1 = array(
            'street' => '',
            'house' => '',
            'flat' => '',
        );

        $expectedResult2 = array(
            'street' => 'Laisvės pr.',
            'house' => '77',
            'flat' => '',
        );

        $expectedResult3 = array(
            'street' => 'Laisves pr.',
            'house' => '77c',
            'flat' => '',
        );

        $expectedResult4_5_6_7 = array(
            'street' => 'Laisves pr.',
            'house' => '77c',
            'flat' => '58',
        );

        $expectedResult8 = array(
            'street' => 'Architektu',
            'house' => '53',
            'flat' => '',
        );

        $expectedResult9 = array(
            'street' => 'Architektų',
            'house' => '53',
            'flat' => '',
        );

        $expectedResult10 = array(
            'street' => 'Architektų g.',
            'house' => '53',
            'flat' => '',
        );

        $expectedResult11 = array(
            'street' => 'Architektų g',
            'house' => '53',
            'flat' => '',
        );

        $expectedResult12 = array(
            'street' => 'Laisves pr.',
            'house' => '77 c',
            'flat' => '',
        );

        // Empty test
        $address1 = '';
        $address2 = 'Laisvės pr. 77';
        $address3 = 'Laisves pr. 77c';
        $address4 = 'Laisves pr. 77c-58';
        $address5 = 'Laisves pr. 77c -58';
        $address6 = 'Laisves pr. 77c - 58';
        $address7 = 'Laisves pr. 77c 58';
        $address8 = 'Architektu 53';
        $address9 = 'Architektų 53';
        $address10 = 'Architektų g. 53';
        $address11 = 'Architektų g 53';
        $address12 = 'Laisves pr. 77 c';

        $gotResult1 = $util->parseAddress($address1);
        $gotResult2 = $util->parseAddress($address2);
        $gotResult3 = $util->parseAddress($address3);
        $gotResult4 = $util->parseAddress($address4);
        $gotResult5 = $util->parseAddress($address5);
        $gotResult6 = $util->parseAddress($address6);
        $gotResult7 = $util->parseAddress($address7);
        $gotResult8 = $util->parseAddress($address8);
        $gotResult9 = $util->parseAddress($address9);
        $gotResult10 = $util->parseAddress($address10);
        $gotResult11 = $util->parseAddress($address11);
        $gotResult12 = $util->parseAddress($address12);

        $this->assertEquals($expectedResult1, $gotResult1);
        $this->assertEquals($expectedResult2, $gotResult2);
        $this->assertEquals($expectedResult3, $gotResult3);
        $this->assertEquals($expectedResult4_5_6_7, $gotResult4);
        $this->assertEquals($expectedResult4_5_6_7, $gotResult5);
        $this->assertEquals($expectedResult4_5_6_7, $gotResult6);
        $this->assertEquals($expectedResult4_5_6_7, $gotResult7);
        $this->assertEquals($expectedResult8, $gotResult8);
        $this->assertEquals($expectedResult9, $gotResult9);
        $this->assertEquals($expectedResult10, $gotResult10);
        $this->assertEquals($expectedResult11, $gotResult11);
        $this->assertEquals($expectedResult12, $gotResult12);
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
