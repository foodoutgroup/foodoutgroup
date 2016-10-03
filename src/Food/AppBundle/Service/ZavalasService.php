<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
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

    public function isZavalasTurnedOnGlobal()
    {
        $zavalasStatus = false;
        if ($this->miscService->getParam('zaval_on')) {
            $zavalasStatus = true;
        }
        return $zavalasStatus;
    }

    public function isZavalasTurnedOnByCity($city)
    {
        $zavalasStatus = false;

        if ($this->isZavalasTurnedOnGlobal()) {
            $activeCities = $this->em->getRepository("FoodAppBundle:City")->findBy(array(
                'title' => $city,
                'zavalasOn' => true
            ));
            if (count($activeCities)) {
                $zavalasStatus = true;
            }
        }
        return $zavalasStatus;
    }

    public function getZavalasTimeByCity($city)
    {
        $zavalasCity = $this->em->getRepository("FoodAppBundle:City")->findOneBy(array(
            'title' => $city
        ));
        return round($zavalasCity->getZavalasTime() / 60, 2) . " " . $this->translator->trans('general.hour');
    }

    public function getZavalasTimeByPlace(Place $place)
    {
        $city = false;

        $locationData = $this->locationService->getLocationFromSession();

        if ($this->isZavalasTurnedOnByCity($locationData['city']) && $this->placesService->isPlaceDeliversToCity($place, $locationData['city'])) {
            $city = $locationData['city'];
        } else {
            $placeCities = $this->em->getRepository('FoodDishesBundle:Place')->getCities($place);
            foreach ($placeCities as $placeCity) {
                $placeCityKey = $this->em->getRepository('FoodAppBundle:City')->getZavalasTimeByTitle($placeCity);
                $placeCitiesOrdered[$placeCityKey] = $placeCity;
            }
            krsort($placeCitiesOrdered);
            $placeCities = $placeCitiesOrdered;
            foreach ($placeCities as $placeCity) {
                if ($this->isZavalasTurnedOnByCity($placeCity)) {
                    $city = $placeCity;
                    break;
                }
            }
        }
        if ($city) {
            $zavalasCity = $this->em->getRepository("FoodAppBundle:City")->findOneBy(array(
                'title' => $city
            ));
            return round($zavalasCity->getZavalasTime() / 60, 2) . " " . $this->translator->trans('general.hour');
        } else {
            return false;
        }


    }
}