<?php 

namespace Food\AppBundle\Test;

use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;

class WebTestCase extends SymfonyWebTestCase
{

    /**
     * @var Client
     */
    protected $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    protected function getContainer()
    {
        return $this->client->getContainer();
    }

    protected function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @param string $placeName
     * @return Place
     */
    protected function getPlace($placeName)
    {
        $om = $this->getDoctrine()->getManager();

        $place = new Place();
        $place->setActive(true)
            ->setCreatedAt(new \DateTime("now"))
            ->setName($placeName)
            ->setDeliveryPrice(5)
            ->setCartMinimum(5)
            ->setDeliveryTime('1 val.')
            ->setPickupTime('30 min')
            ->setSelfDelivery(false)
            ->setCardOnDelivery(false)
            ->setNew(false)
            ->setRecommended(true);

        $om->persist($place);
        $om->flush();

        return $place;
    }

    /**
     * @param Place $place
     * @return PlacePoint
     */
    protected function getPlacePoint($place)
    {
        $om = $this->getDoctrine()->getManager();

        $placePoint = new PlacePoint();
        $placePoint->setPlace($place)
            ->setCreatedAt(new \DateTime("now"))
            ->setCompanyCode('12345')
            ->setLat('123')
            ->setLon('345')
            ->setDeliveryTime('1 val.')
            ->setPhone('37061212122')
            ->setCity('Vilnius')
            ->setAddress('Test address 123')
            ->setActive(true)
            ->setPublic(true)
            ->setWd1Start('9:00')
            ->setWd1End('22:00')
            ->setWd2Start('9:00')
            ->setWd2End('22:00')
            ->setWd3Start('9:00')
            ->setWd3End('22:00')
            ->setWd4Start('9:00')
            ->setWd4End('22:00')
            ->setWd5Start('9:00')
            ->setWd5End('22:00')
            ->setWd6Start('9:00')
            ->setWd6End('22:00')
            ->setWd7Start('9:00')
            ->setWd7End('22:00');

        $om->persist($placePoint);
        $om->flush();

        return $placePoint;
    }

    /**
     * @param Place $place
     * @param PlacePoint $placePoint
     * @param string $status
     * @return Order
     */
    protected function getOrder($place, $placePoint, $status)
    {
        $om = $this->getDoctrine()->getManager();

        $order = new Order();
        $order->setOrderDate(new \DateTime("now"))
            ->setPlace($place)
            ->setPlacePoint($placePoint)
            ->setPlacePointCity($placePoint->getCity())
            ->setPlacePointAddress($placePoint->getAddress())
            ->setOrderStatus($status)
            ->setVat('21')
            ->setTotal('100')
            ->setOrderHash('sadasfdafsf')
            ->setPaymentMethod('local')
            ->setPaymentStatus('complete')
            ->setLocale('lt');

        $om->persist($order);
        $om->flush();

        return $order;
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        $um = $this->getContainer()->get('fos_user.user_manager');

        $user = $um->findUserByEmail('order_api_buyer@foodout.lt');

        if (!$user) {
            $user = $um->createUser();
            $user->setEmail('order_api_buyer@foodout.lt');
            $user->setPlainPassword('123488');
            $user->setFirstname('Api buyer');
            $user->setPhone('37061234567');

            $user->setRoles(array('ROLE_USER'));
            $user->setFullyRegistered(1);
            $user->setEnabled(true);

            $um->updateUser($user);
        }

        return $user;
    }

    /**
     * @param User $user
     * @return UserAddress
     */
    protected function getAddress($user)
    {
        $om = $this->getDoctrine()->getManager();

        $address = $user->getDefaultAddress();

        if (!$address || empty($address)) {
            $address = new UserAddress();
            $address->setAddress('Galvydzio 5')
                ->setCity('Vilnius')
                ->setLat('54.15424')
                ->setLon('24.1242')
                ->setUser($user)
                ->setDefault(1);
            $om->persist($address);
            $om->flush();
        }

        return $address;
    }
}
