<?php

namespace Food\AppBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class MailerService
{
    private $provider = null;

    public function __construct(Container $container, $provider)
    {
        $this->container = $container;
        $this->provider = $this->container->get($provider);
    }

    public function setTest()
    {
        return $this->provider->setTest();
    }

    public function setRecipient($email, $name = '')
    {
        return $this->provider->setRecipient($email, $name);
    }

    public function setFromName($name)
    {
        return $this->provider->setFromName($name);
    }

    public function setFromEmail( $email )
    {
        return $this->provider->setFromEmail($email);
    }

    public function setVariables($variables)
    {
        return $this->provider->setVariables($variables);
    }

    public function resetVariables()
    {
        return $this->provider->resetVariables();
    }

    public function setVariable($name, $value)
    {
        return $this->provider->setVariable();
    }

    public function setId($id)
    {
        return $this->provider->setId($id);
    }

    public function setType($type)
    {
        return $this->provider->setType($type);
    }

    public function setLanguage($code)
    {
        return $this->provider->setLanguage($code);
    }

    public function addRecipient($recipient)
    {
        return $this->provider->addRecipient($recipient);
    }

    public function addRecipients($recipients)
    {
        return $this->provider->addRecipients($recipients);
    }

    public function setReplyTo($email, $name = '')
    {
        return $this->provider->setReplyTo($email, $name);
    }

    public function addAttachment($filename, $content)
    {
        return $this->provider->addAttachment($filename, $content);
    }

    public function removeAttachments()
    {
        return $this->provider->removeAttachments();
    }

    public function send()
    {
        return $this->provider->send();
    }

    public function addAll($subscribers, $resubscribe = 0)
    {
        return $this->provider->addAll($subscribers, $resubscribe);
    }
}
