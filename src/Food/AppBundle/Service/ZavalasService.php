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


    /**
     * @return bool
     */
    public function isRushHourEnabled()
    {
        return $this->isRushHourOnGlobal();
    }

    /**
     * @return bool
     * @deprecated 2017-04-05
     */
    public function isRushHourOnGlobal()
    {
        return (boolean) $this->miscService->getParam('zaval_on', false);
    }

    /**
     * @param City $city
     * @return bool
     */
    public function isRushHourAtCity($city)
    {
        if(!$city) {
            return false;
        }
        return $city->isZavalasOn();
    }

    /**
     * @param int $cityId
     * @return bool
     */
    public function isRushHourAtCityById($cityId)
    {
        return $this->isRushHourAtCity($this->em->getRepository("FoodAppBundle:City")->find($cityId));
    }

    /**
     * @param City $city
     * @return bool|string
     */
    public function getRushHourTimeAtCity($city)
    {
        if(!$city) {
            return false;
        }
        return round($city->getZavalasTime() / 60, 2) . " " . $this->translator->trans('general.hour');
    }

    /**
     * @param int $cityId
     * @return bool
     */
    public function getRushHourTimeAtCityById($cityId)
    {
        return $this->getRushHourTimeAtCity($this->em->getRepository("FoodAppBundle:City")->find($cityId));
    }

    /**
     * @param Place $place
     * @return bool
     */
    public function getRushHourTimeByPlace(Place $place)
    {
        $return = false;
        $locationData = $this->locationService->get();
        if ($locationData['city_id']) {
            $cityObj = $this->em->getRepository('FoodAppBundle:City')->find($locationData['city_id']);

            $deliversToCity = $this->placesService->isPlaceDeliversToCity($place, $cityObj->getId());

            if ($this->isRushHourAtCity($cityObj) && $deliversToCity) {
                $return = $this->getRushHourTimeAtCity($cityObj);
            } elseif (!$deliversToCity) {

                $placeCityCollection = $this->em->getRepository('FoodDishesBundle:Place')->getCityCollectionByPlace($place);
                $placeCityCollectionOrdered = [];
                foreach ($placeCityCollection as $placeCity) {
                    $placeCityKey = $this->em->getRepository('FoodAppBundle:City')->find($placeCity->getId())->getZavalasTime();
                    if ($this->isRushHourAtCity($placeCity)) {
                        $placeCityCollectionOrdered[$placeCityKey] = $placeCity;
                    }
                }
                krsort($placeCityCollectionOrdered);
                foreach ($placeCityCollectionOrdered as $placeCity) {
                    $return = $this->getRushHourTimeAtCity($placeCity);
                    break;
                }
            }
        }
        return $return;
    }
}
