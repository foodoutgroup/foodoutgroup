<?php

namespace Food\PushBundle\Service;

use Food\OrderBundle\Entity\Order;
use \Food\PushBundle\Entity\Push;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class PushService
 *
 *
 * @package Food\PushBundle\Service
 */
class PushService
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var null
     */
    private $manager = null;

    /**
     * @param                      $container
     */
    public function __construct($container)
    {
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
     * Creates Push entity
     *
     * @param string $token
     * @param string $text
     * @param Order|null
     *
     * @return Push
     */
    public function createPush($token = null, $text = null, $order = null)
    {
        $push = new Push();
        $push->setCreatedAt(new \DateTime("now"));

        if (!empty($text)) {
            $text = str_replace(["\n", "\t", "\r"], '', $text);
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
            $push->setMessage($text);
        }
        if (!empty($order)) {
            $push->setOrder($order);
        }
        $push->setSent(false);
        $push->setToken($token);

        return $push;
    }

    /**
     * @param Push $push
     *
     * @throws \Exception
     */
    public function savePush($push)
    {
        if (!($push instanceof Push)) {

            throw new \Exception('Message not given. How should I save it?');
        }

        if (strlen($push->getMessage()) < 1) {
            return false;
        }

        $em = $this->getManager();

        $em->persist($push);
        $em->flush();

        return true;
    }

    public function sendPush($push)
    {
        $locale = $this->getContainer()->get('request')->getLocale();

        $content = array(
            $locale => $push->getMessage()
        );


        $fields = array(
            'app_id' => $this->getContainer()->getParameter('signal_id'),
            'include_player_ids' => array($push->getToken()),
            'data' => array("foo" => "bar"),
            'contents' => $content
        );

        $fields = json_encode($fields);
        print("\nJSON sent:\n");
        print($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $this->getContainer()->getParameter('signal_authentication')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}
