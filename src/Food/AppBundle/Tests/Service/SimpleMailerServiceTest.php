<?php

namespace Food\AppBundle\Tests\Service;

use Food\AppBundle\Service\SimpleMailerService;

class SimpleMailerServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $simpleMailer;

    public function setUp()
    {
        $this->simpleMailer = new SimpleMailerService();

        $mailerMock = $this->getMockBuilder('\StdClass')
                           ->setMethods(['createMessage',
                                         'send',
                                         'getTransport',
                                         'getSpool',
                                         'flushQueue'])
                           ->getMock();

        $transportMock = $this->getMockBuilder('\StdClass')
                              ->getMock();

        $messageMock = $this->getMockBuilder('\StdClass')
                            ->setMethods(['setFrom',
                                          'setTo',
                                          'setSubject',
                                          'setBody'])
                            ->getMock();

        $mailerMock->expects($this->any())
                   ->method('createMessage')
                   ->willReturn($messageMock);

        $mailerMock->expects($this->any())
                   ->method('send')
                   ->willReturn(null);

        $mailerMock->expects($this->any())
                   ->method('flushQueue')
                   ->willReturn(1);

        $mailerMock->expects($this->any())
                   ->method($this->anything())
                   ->willReturn($mailerMock);

        $messageMock->expects($this->any())
                    ->method($this->anything())
                    ->willReturn($messageMock);

        $this->simpleMailer->setMailer($mailerMock);
        $this->simpleMailer->setTransport($transportMock);
    }

    public function testSetGetMailer()
    {
        $name = 'John';

        $result = $this->simpleMailer->setMailer($name);

        $this->assertSame($result, $this->simpleMailer);
        $this->assertSame($this->simpleMailer->getMailer(), $name);
    }

    public function testSetGetTransport()
    {
        $name = 'John';

        $result = $this->simpleMailer->setTransport($name);

        $this->assertSame($result, $this->simpleMailer);
        $this->assertSame($this->simpleMailer->getTransport(), $name);
    }

    public function testSend()
    {
        $from = 'some@email.com';
        $to = 'some2@email.com';
        $subject = 'subject line';
        $body = 'body line';

        $result = $this->simpleMailer->send($from, $to, $subject, $body);

        $this->assertSame(1, $result);
    }
}
