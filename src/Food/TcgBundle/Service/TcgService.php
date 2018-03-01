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

    public function sendPush()
    {
//        $locale = $this->getContainer()->get('request')->getLocale();

//        $content = array(
//            $locale => $push->getMessage()
//        );


        $fields = array(
            "phonebook" => 4378,
            "contact" => '+37060751091',
            "status" => 1,
            "last_name" => "Kozlovas",
            "first_name" => "Matas",
            "email" => "matas@foodout.lt",
            "address" => "kavoliuko g. 9 Vilnius",
            "city" => "Vilnius",
            "state" => "Vilnius",
            "country" => 'Lithuania',
            "unit_number" => 1321321,
            "additional_vars" => null,
            "description" => "Description"
        );

        $fields = json_encode($fields);
        print("\nJSON sent:\n");
        print($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://dial2.tcg.lt/rest-api/contact");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . 'foodout:Foodout478#'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_USERPWD, "foodout:Foodout478#");
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}
