<?php

namespace Food\TcgBundle\Service;

use Food\OrderBundle\Entity\Order;
use \Food\PushBundle\Entity\Push;
use Food\TcgBundle\Entity\TcgLog;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class PushService
 *
 *
 * @package Food\PushBundle\Service
 */
class TcgService
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

    public function createLog(Order $order)
    {
        $tcgLog = new TcgLog();
        $tcgLog->setCreatedAt(new \DateTime("now"));
        $tcgLog->setOrder($order);
        $tcgLog->setSent(false);
        $tcgLog->setPhone($order->getPlacePoint()->getPhoneNiceFormat());
        $tcgLog->setSubmittedAt(new \DateTime("now"));
        return $tcgLog;
    }

    public function saveLog($tcgLog)
    {
        if (!($tcgLog instanceof TcgLog)) {

            throw new \Exception('Message not given. How should I save it?');
        }

        $em = $this->getManager();

        $em->persist($tcgLog);
        $em->flush();

        return true;
    }

    public function sendPush(Order $order)
    {
        $return = [];

        $fields = array(
            "phonebook" => (string)$this->container->getParameter('tcg_book_id'),
            "contact" => $order->getPlacePoint()->getPhoneNiceFormat(),
            "status" => 1,
            "last_name" => 'N1',
            "first_name" => 'N2',
            "email" => '',
            "address" => '',
            "city" => '',
            "state" => '',
            "country" => '',
            "unit_number" => '',
            "additional_vars" => '',
            "description" => ''
        );

        $fields = json_encode($fields);

        $ch = curl_init($this->container->getParameter('tcg_push_url'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->container->getParameter('tcg_username') . ":" . $this->container->getParameter('tcg_pass'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch)['http_code'];

        curl_close($ch);
        $return[$code] = $response;

        return $return;
    }

}
