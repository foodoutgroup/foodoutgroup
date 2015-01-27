<?php

namespace Food\AppBundle\Tests\Service;

use Food\AppBundle\Service\SimpleMailerService;

class SimpleMailerServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $simpleMailer;

    public function setUp()
    {
        $this->simpleMailer = new SimpleMailerService();
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
        $this->simpleMailer->setMailer($this->getMailerMock());
        $this->simpleMailer->setTransport($this->getTransportMock());

        $from = 'some@email.com';
        $to = 'some2@email.com';
        $subject = 'subject line';
        $body = 'body line';

        $result = $this->simpleMailer->send($from, $to, $subject, $body);

        $this->assertSame(1, $result);
    }

    public function getMailerMock()
    {
        $mailerMock = $this->getMockBuilder('\StdClass')
                           ->setMethods(['createMessage',
                                         'send',
                                         'getTransport',
                                         'getSpool',
                                         'flushQueue'])
                           ->getMock();

        // we have three main points in send() method:
        // 1. createMessage()
        // 2. send()
        // 3. flushQueue()
        $mailerMock->expects($this->any())
                   ->method('createMessage')
                   ->willReturn($this->getMessageMock());

        $mailerMock->expects($this->any())
                   ->method('send')
                   ->willReturn(null);

        $mailerMock->expects($this->any())
                   ->method('flushQueue')
                   ->willReturn(1);

        // the rest is useless
        $mailerMock->expects($this->any())
                   ->method($this->anything())
                   ->willReturn($mailerMock);

        return $mailerMock;
    }

    public function getTransportMock()
    {
        $transportMock = $this->getMockBuilder('\StdClass')
                              ->getMock();

        return $transportMock;
    }

    public function getMessageMock()
    {
        $messageMock = $this->getMockBuilder('\StdClass')
                            ->setMethods(['setFrom',
                                          'setTo',
                                          'setSubject',
                                          'setBody'])
                            ->getMock();

        $messageMock->expects($this->any())
                    ->method($this->anything())
                    ->willReturn($messageMock);

        return $messageMock;
    }
}
