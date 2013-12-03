<?php

namespace Food\SmsBundle\Service;

use \Food\SmsBundle\Entity\Message;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class MessagesService
 *
 *
 * @package Food\SmsBundle\Service
 */
class MessagesService {
    /**
     * @var SmsProviderInterface
     */
    private $messagingProvider = null;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param $container
     * @param SmsProviderInterface $messagingProvider
     */
    public function __construct($container, $messagingProvider = null)
    {
        $this->messagingProvider = $messagingProvider;
        $this->container = $container;
    }

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param \Food\SmsBundle\Service\SmsProviderInterface $messagingProvider
     */
    public function setMessagingProvider($messagingProvider)
    {
        $this->messagingProvider = $messagingProvider;
    }

    /**
     * @return \Food\SmsBundle\Service\SmsProviderInterface
     */
    public function getMessagingProvider()
    {
        return $this->messagingProvider;
    }

    public function getAccountBalance()
    {
        $this->getMessagingProvider()->authenticate('skanu', 'test');
        return $this->getMessagingProvider()->getAccountBalance();
    }

    /**
     * TODO
     * @param int $id
     * @return bool|Message
     */
    public function getMessage($id)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $message = $em->getRepository('Food\SmsBundle\Entity\Message')->find($id);

        if (!$message) {
            return false;
        }

        return $message;
    }

    /**
     * @param Message $message
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        if (!($message instanceof Message)) {
            throw new \Exception('Message not given. How should I save it?');
        }
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($message);
        $em->flush();
    }

    /**
     * @param \Food\SmsBundle\Entity\Message $message
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function sendMessage($message)
    {
        if (!($message instanceof Message)) {
            throw new \InvalidArgumentException("Wrong message type given");
        }

        try {
            $status = $this->getMessagingProvider()->sendMessage(
                $message->getSender(),
                $message->getRecipient(),
                $message->getMessage()
            );

            $message->setSent($status['sent'])
                ->setSubmittedAt(new \DateTime("now"))
                ->setExtId($status['messageid'])
                ->setLastSendingError($status['error']);

        // TODO Noramlus exception handlingas cia ir servise (https://basecamp.com/2470154/projects/4420182-skanu-lt-gamyba/todos/73047842-pilnas-exception)
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getMessageStatus()
    {
        // TODO https://basecamp.com/2470154/projects/4420182-skanu-lt-gamyba/todos/73004448-delivery-apdorojimas#events_todo_73004448
        $this->getMessagingProvider()->authenticate('skanu', 'test');
//        return $this->getMessagingProvider()->getMessageStatus();
    }


    // TODO
}