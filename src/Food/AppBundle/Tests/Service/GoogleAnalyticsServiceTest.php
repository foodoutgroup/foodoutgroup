<?php

namespace Food\AppBundle\Tests\Service;

use Food\AppBundle\Service\GoogleAnalyticsService;

class GoogleAnalyticsServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testViewIdSetterAndGetter()
    {
        $gaService = new GoogleAnalyticsService();

        $setterResult = $gaService->setViewId('12345');
        $getterResult = $gaService->getViewid();

        $this->assertSame($setterResult, $gaService);
        $this->assertSame('12345', $getterResult);
    }

    public function testPrivateKeySetterAndGetter()
    {
        $gaService = new GoogleAnalyticsService();

        $setterResult = $gaService->setPrivateKey(base64_encode('12345'));
        $getterResult = $gaService->getPrivateKey();

        $this->assertSame($setterResult, $gaService);
        $this->assertSame($getterResult, '12345');
    }

    public function testScopesSetterAndGetter()
    {
        $gaService = new GoogleAnalyticsService();

        $setterResult = $gaService->setScopes([1, 2, 'three']);
        $getterResult = $gaService->getScopes();

        $this->assertSame($setterResult, $gaService);
        $this->assertSame([1, 2, 'three'], $getterResult);
    }

    public function testServiceAccountNameSetterAndGetter()
    {
        $gaService = new GoogleAnalyticsService();

        $setterResult = $gaService->setServiceAccountName('abc');
        $getterResult = $gaService->getServiceAccountName();

        $this->assertSame($setterResult, $gaService);
        $this->assertSame('abc', $getterResult);
    }

    public function testGetServiceReturnsGoogleAnalyticsServiceInstance()
    {
        $gaService = new GoogleAnalyticsService();
        $gaService->setServiceAccountName('some name');
        $gaService->setScopes(['some scope']);
        $gaService->setPrivateKey('some private key');

        $result = $gaService->getService();

        $this->assertInstanceOf('\Google_Service_Analytics', $result);
    }

    public function testGetPageviewsReturnsANumber()
    {
        $mock1 = $this->getMockBuilder('\StdClass')
                      ->setMethods(['get', 'getTotalsForAllResults'])
                      ->getMock();
        $mock1->expects($this->any())
              ->method('get')
              ->willReturn($mock1);

        $mock1->expects($this->any())
              ->method('getTotalsForAllResults')
              ->willReturn([GoogleAnalyticsService::GA_PAGEVIEWS => '12345']);

        $mock2 = new \StdClass();
        $mock2->data_ga = $mock1;

        $gaService = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                          ->setMethods(['getService'])
                          ->getMock();

        $gaService->expects($this->once())
                  ->method('getService')
                  ->willReturn($mock2);

        $result = $gaService->getPageviews('from', 'to');

        $this->assertRegExp('#(\d+|\d+\.\d+)#', $result);
    }

    public function testGetUniquePageviewsReturnsANumber()
    {
        $mock1 = $this->getMockBuilder('\StdClass')
                      ->setMethods(['get', 'getTotalsForAllResults'])
                      ->getMock();
        $mock1->expects($this->any())
              ->method('get')
              ->willReturn($mock1);

        $mock1->expects($this->any())
              ->method('getTotalsForAllResults')
              ->willReturn([GoogleAnalyticsService::GA_UNIQUE_PAGEVIEWS => '12345']);

        $mock2 = new \StdClass();
        $mock2->data_ga = $mock1;

        $gaService = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                          ->setMethods(['getService'])
                          ->getMock();

        $gaService->expects($this->once())
                  ->method('getService')
                  ->willReturn($mock2);

        $result = $gaService->getUniquePageviews('from', 'to');

        $this->assertRegExp('#(\d+|\d+\.\d+)#', $result);
    }

    public function testGetUsersReturnsANumber()
    {
        $mock1 = $this->getMockBuilder('\StdClass')
                      ->setMethods(['get', 'getTotalsForAllResults'])
                      ->getMock();
        $mock1->expects($this->any())
              ->method('get')
              ->willReturn($mock1);

        $mock1->expects($this->any())
              ->method('getTotalsForAllResults')
              ->willReturn([GoogleAnalyticsService::GA_USERS => '12345']);

        $mock2 = new \StdClass();
        $mock2->data_ga = $mock1;

        $gaService = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                          ->setMethods(['getService'])
                          ->getMock();

        $gaService->expects($this->once())
                  ->method('getService')
                  ->willReturn($mock2);

        $result = $gaService->getUsers('from', 'to');

        $this->assertRegExp('#(\d+|\d+\.\d+)#', $result);
    }

    public function testGetRegturningUsersReturnsANumber()
    {
        $mock1 = $this->getMockBuilder('\StdClass')
                      ->setMethods(['get', 'getRows'])
                      ->getMock();
        $mock1->expects($this->any())
              ->method('get')
              ->willReturn($mock1);

        $mock1->expects($this->any())
              ->method('getRows')
              ->willReturn([['Returning Visitor', '12345']]);

        $mock2 = new \StdClass();
        $mock2->data_ga = $mock1;

        $gaService = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                          ->setMethods(['getService'])
                          ->getMock();

        $gaService->expects($this->once())
                  ->method('getService')
                  ->willReturn($mock2);

        $result = $gaService->getReturningUsers('from', 'to');

        $this->assertRegExp('#(\d+|\d+\.\d+)#', $result);
    }

    public function testGetRegturningUsersReturnsAMinus1()
    {
        $mock1 = $this->getMockBuilder('\StdClass')
                      ->setMethods(['get', 'getRows'])
                      ->getMock();
        $mock1->expects($this->any())
              ->method('get')
              ->willReturn($mock1);

        $mock1->expects($this->any())
              ->method('getRows')
              ->willReturn([['some irrelavant value', '12345']]);

        $mock2 = new \StdClass();
        $mock2->data_ga = $mock1;

        $gaService = $this->getMockBuilder('\Food\AppBundle\Service\GoogleAnalyticsService')
                          ->setMethods(['getService'])
                          ->getMock();

        $gaService->expects($this->once())
                  ->method('getService')
                  ->willReturn($mock2);

        $result = $gaService->getReturningUsers('from', 'to');

        $this->assertSame(-1, $result);
    }
}
