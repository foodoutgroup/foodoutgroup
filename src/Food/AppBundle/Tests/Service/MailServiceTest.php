<?php

namespace Food\AppBundle\Tests\Service;

use Food\AppBundle\Entity\EmailToSend;
use Food\AppBundle\Service\MailService;
use Food\OrderBundle\Entity\Order;

class MailServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can not schedule email sending - non order given
     */
    public function testAddEmailToSendNoOrderException()
    {
        $mailService = new MailService();
        $mailService->addEmailForSend(null, null, null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can not schedule email sending - unknown type of email: ""
     */
    public function testAddEmailToSendNoTypeException()
    {
        $mailService = new MailService();
        $mailService->addEmailForSend(new Order(), null, null);
    }

    public function testAddEmailToSendNoDateGiven()
    {
        $mailService = new MailService();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mailService->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $order = new Order();
        $emailToSend = new EmailToSend();
        $emailToSend->setOrder($order)
            ->setType('order_completed')
            ->setCreatedAt(new \DateTime('now'))
            ->setSendOnDate(new \DateTime('now'));

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($emailToSend);

        $entityManager->expects($this->once())
            ->method('flush');

        $mailService->addEmailForSend($order, 'order_completed', null);
    }

    public function testAddEmailToSendWithDate()
    {
        $mailService = new MailService();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mailService->setContainer($container);

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $order = new Order();
        $theDate = new \DateTime('2012-01-15');
        $emailToSend = new EmailToSend();
        $emailToSend->setOrder($order)
            ->setType('order_completed')
            ->setCreatedAt(new \DateTime('now'))
            ->setSendOnDate($theDate);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($emailToSend);

        $entityManager->expects($this->once())
            ->method('flush');

        $mailService->addEmailForSend($order, 'order_completed', $theDate);
    }
}
