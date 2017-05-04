<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Utils\Misc;
use Food\DishesBundle\Entity\Place;
use Food\PlacesBundle\Service\PlacesService;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

class ZavalasService extends BaseService
{

    protected $miscService;
    protected $translator;
    protected $locationService;
    protected $placesService;

    public function __construct(EntityManager $em, Misc $miscService, Translator $translator, LocationService $locationService, PlacesService $placesService)
    {
        parent::__construct($em);
        $this->miscService = $miscService;
        $this->translator = $translator;
        $this->locationService = $locationService;
        $this->placesService = $placesService;
    }

    public function isRushHourOnGlobal()
    {
        return $this->miscService->getParam('zaval_on', false);
    }

    public function isRushHourAtCity(City $city)
    {
        $rushHour = false;
        if ($this->isRushHourAtCity($city)) {
            $rushHour = $city->isZavalasOn();
        }
        return $rushHour;
    }

    public function isRushHourAtCityById($cityId)
    {
        return $this->isRushHourAtCity($this->em->getRepository("FoodAppBundle:City")->find($cityId));
    }

    public function getRushHourTimeAtCity(City $city)
    {
        return round($city->getZavalasTime() / 60, 2) . " " . $this->translator->trans('general.hour');
    }

    public function getRushHourTimeAtCityById($cityId)
    {
        return $this->getRushHourTimeAtCity($this->em->getRepository("FoodAppBundle:City")->find($cityId));
    }

    public function getRushHourTimeByPlace(Place $place)
    {
        $locationData = $this->locationService->getLocationFromSession();
        $cityObj = $this->em->getRepository('FoodAppBundle:City')->find($locationData['city_id']);

        if (!$cityObj || !$this->isRushHourAtCity($cityObj) || !$this->placesService->isPlaceDeliversToCity($place, $cityObj->getId())) {

            $placeCityCollection = $this->em->getRepository('FoodDishesBundle:Place')->getCityCollectionByPlace($place);
            $placeCityCollectionOrdered = [];
            foreach ($placeCityCollection as $placeCity) {
                $placeCityKey = $this->em->getRepository('FoodAppBundle:City')->find($placeCity->getId())->getZavalasTime();
                $placeCityCollectionOrdered[$placeCityKey] = $placeCity;
            }
            krsort($placeCityCollectionOrdered);
            $placeCityCollection = $placeCityCollectionOrdered;
            foreach ($placeCityCollection as $placeCity) {
                if ($this->isRushHourAtCity($placeCity)) {
                    return $this->getRushHourTimeAtCity($placeCity);
                }
            }
        }
        return false;
    }
}