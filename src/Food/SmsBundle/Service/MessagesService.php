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
     * @var null
     */
    private $manager = null;

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
     * @param null $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager()
    {
        if (empty($this->manager)) {
            $this->manager = $this->getContainer()->get('doctrine')->getManager();
        }
        return $this->manager;
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
        return $this->getMessagingProvider()->getAccountBalance();
    }

    /**
     * Get message by ID
     *
     * @param int $id
     * @return bool|Message
     */
    public function getMessage($id)
    {
        $em = $this->getManager();
        $message = $em->getRepository('Food\SmsBundle\Entity\Message')->find($id);

        if (!$message) {
            return false;
        }

        return $message;
    }

    /**
     * Creates message entity
     *
     * @param string $sender
     * @param string $recipient
     * @param string $text
     *
     * @return Message
     */
    public function createMessage($sender=null, $recipient=null, $text=null)
    {
        $message = new Message();
        $message->setCreatedAt(new \DateTime("now"));

        if (!empty($sender)) {
            $message->setSender($sender);
        }
        if (!empty($recipient)) {
            $recipient = str_replace('+', '', $recipient);

            $message->setRecipient($recipient);
        }
        if (!empty($text)) {
            $message->setMessage($text);
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
        $em = $this->getManager();
        $em->persist($message);
        $em->flush();
    }

    /**
     * Get message by ext id
     *
     * @param int|string $extId
     * @throws \Exception
     * @return bool|Message
     */
    public function getMessageByExtId($extId)
    {
        $em = $this->getManager();
        $message = $em->getRepository('Food\SmsBundle\Entity\Message')->findBy(array('extId' => $extId), null, 1);

        if (!$message) {
            return false;
        }

        if (count($message) > 1) {
            throw new \Exception('More then one message found. How the hell? Ext id: '.$extId);
        }

        // TODO negrazu, bet laikina :(
        $message = $message[0];

        return $message;
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
                ->setLastSendingError($status['error'])
                ->setSmsc($this->getMessagingProvider()->getProviderName())
                ->setTimesSent($message->getTimesSent()+1);

        // TODO Noramlus exception handlingas cia ir servise (https://basecamp.com/2470154/projects/4420182-skanu-lt-gamyba/todos/73047842-pilnas-exception)
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param string|array $dlrData
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function updateMessagesDelivery($dlrData)
    {
        $logger = $this->container->get("logger");
        $logger->info('-- updateMessageDelivery:  --');
        $logger->info(print_r($dlrData, true));
        if (empty($dlrData)) {
            throw new \InvalidArgumentException('No DLR data received');
        }

        $messageDeliveries = $this->getMessagingProvider()->parseDeliveryReport($dlrData);

        try {
            if (!empty($messageDeliveries)) {
                foreach ($messageDeliveries as $messageData) {
                    $message = $this->getMessageByExtId($messageData['extId']);

                    if (!$message) {
                        // TODO normalus exceptionas, kuri kitaip handlinsim
                        throw new \InvalidArgumentException('Message not found!');
                    } else {
                        $logger->info(print_r($message, true));

                        $message->setDelivered($messageData['delivered']);

                        if ($messageData['delivered'] == true) {
                            $message->setReceivedAt(new \DateTime($messageData['completeDate']))
                                ->setLastSendingError($messageData['error'])
                                ->setLastErrorDate(null);
                        } else {
                            $message->setDelivered(false)
                                ->setLastSendingError($messageData['error'])
                                ->setLastErrorDate(new \DateTime($messageData['completeDate']));
                        }

                        $this->saveMessage($message);
                    }
                }
            }

            // TODO Noramlus exception handlingas cia ir servise (https://basecamp.com/2470154/projects/4420182-skanu-lt-gamyba/todos/73047842-pilnas-exception)
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     */
    protected function getUnsentMessagesQuery()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository('FoodSmsBundle:Message');

        $queryBuilder = $repository->createQueryBuilder('m')
            ->where('m.sent = 0')
            ->orderBy('m.createdAt', 'ASC');

        return  $queryBuilder;
    }

    /**
     * @return array
     */
    public function getUnsentMessages()
    {
        $query = $this->getUnsentMessagesQuery()
            ->andWhere('m.createdAt >= :yesterday')
            ->andWhere('m.timesSent < 5')
            ->setParameter('yesterday', new \DateTime('-1 days'))
            ->getQuery();


        $messages = $query->getResult();
        if (!$messages) {
            return array();
        }

        return $messages;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getUnsentMessagesForRange(\DateTime $from, \DateTime $to)
    {
        $query = $this->getUnsentMessagesQuery()
            ->andWhere('m.createdAt >= :from_date')
            ->andWhere('m.createdAt <= :to_date')
            ->setParameter('from_date', $from)
            ->setParameter('to_date', $to)
            ->getQuery();


        $messages = $query->getResult();
        if (!$messages) {
            return array();
        }

        return $messages;
    }

    /**
     * @return mixed
     */
    protected function getUndeliveredMessagesQuery()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository('FoodSmsBundle:Message');

        $queryBuilder = $repository->createQueryBuilder('m')
            ->where('m.sent = 1')
            ->andWhere('m.delivered = 0')
            ->orderBy('m.createdAt', 'ASC');

        return  $queryBuilder;
    }

    /**
     * @return array
     */
    public function getUndeliveredMessages()
    {
        $query = $this->getUndeliveredMessagesQuery()
            ->andWhere('m.submittedAt >= :yesterday')
            ->andWhere('m.submittedAt <= :sentJustNow')
            ->setParameter('yesterday', new \DateTime('-1 days'))
            ->setParameter('sentJustNow', new \DateTime('-6 minutes'))
            ->getQuery();

        $messages = $query->getResult();
        if (!$messages) {
            return array();
        }

        return $messages;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getUndeliveredMessagesForRange(\DateTime $from, \DateTime $to)
    {
        $query = $this->getUndeliveredMessagesQuery()
            ->andWhere('m.submittedAt >= :from_date')
            ->andWhere('m.submittedAt <= :to_date')
            ->setParameter('from_date', $from)
            ->setParameter('to_date', $to)
            ->getQuery();


        $messages = $query->getResult();
        if (!$messages) {
            return array();
        }

        return $messages;
    }
}