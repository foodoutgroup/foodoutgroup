<?php

namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Entity\Tmp\Location;
use Food\AppBundle\Utils\Language;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CityService extends BaseService
{
    protected $router;
    protected $locale;
    protected $request;

    public function __construct(EntityManager $em, Router $router, ContainerInterface $container)
    {
        parent::__construct($em);
        $this->router = $router;
        $this->request = $container->get('request');
        $this->locale = $this->request->getLocale();
    }

    public function getActiveCity()
    {
        return $this->em->getRepository('FoodAppBundle:City')->getActive();
    }

    public function getDefaultCity()
    {
        return $this->em->getRepository('FoodAppBundle:City')->findOneBy([], ['position' => 'ASC']);
    }

    public function getCityById($cityId)
    {
        return $this->em->getRepository('FoodAppBundle:City')->findOneBy(['id' => $cityId]);
    }

    public function getCityBySlug($slug)
    {
        return $this->em->getRepository('FoodAppBundle:City')->findOneBy(['slug' => $slug]);
    }

    public function getCityByName($name)
    {
        return $this->em->getRepository('FoodAppBundle:City')->findOneBy(['title' => $name]);
    }

    public function getRandomBestOffers($cityId)
    {

        $bestOfferIds = $this->em->getRepository('FoodAppBundle:City')->getBestOffersByCity($cityId);

        if (!empty($bestOfferIds)) {


            foreach ($bestOfferIds as $item) {
                $tmpOfferIds[] = $item['id'];
            }

            shuffle($tmpOfferIds);

            $bestOfferIds = array_slice($tmpOfferIds, 0, 5);
            $bestOffers = $this->em->getRepository('FoodPlacesBundle:BestOffer')->getBestOffersByIds($bestOfferIds);
        } else {
            $bestOffers = null;
        }
        return $bestOffers;

    }

    public function getPopUpAvailability(City $city)
    {
        $result = false;

        $popTime = $city->getPopUpTimeFrom();
        $popTimeTo = $city->getPopUpTimeTo();
        if ($city->getPopUp()) {
            if (!empty($popTime) && !empty($popTimeTo)) {
                $timeFrom = strtotime($city->getPopUpTimeFrom()->format('H:i:s'));
                $timeTo = strtotime($city->getPopUpTimeTo()->format('H:i:s'));
                $timeNow = strtotime(date('H:i:s'));
                if (($timeFrom <= $timeNow) && ($timeNow <= $timeTo)) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    public function getCityFromLocation($location)
    {
        $result = false;

        $cityObj = $this->getCityById($location['city_id']);

        if ($cityObj) {
            if ($cityObj->getBadge()) {
                $result = true;
            }
        }
        return $result;
    }
}