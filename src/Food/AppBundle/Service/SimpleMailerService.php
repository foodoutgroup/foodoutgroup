<?php

namespace Food\AppBundle\Service;

class SimpleMailerService
{
    protected $mailer;
    protected $transport;

    public function send($from, $to, $subject, $body)
    {
        $message = $this->getMailer()->createMessage();
        $message->setFrom($from)
                ->setTo($to)
                ->setSubject($subject)
                ->setBody($body);

        $this->getMailer()->send($message);
        $amountOfSent = $this
            ->getMailer()
            ->getTransport()
            ->getSpool()
            ->flushQueue($this->getTransport());

        return $amountOfSent;
    }

    public function setMailer($service)
    {
        $this->mailer = $service;
        return $this;
    }

    public function getMailer()
    {
        return $this->mailer;
    }

    public function setTransport($service)
    {
        $this->transport = $service;
        return $this;
    }

    public function getTransport()
    {
        return $this->transport;
    }
}
