<?php
namespace Food\PlacesBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Food\AppBundle\Traits;

class PlacesService extends ContainerAware {
    use Traits\Service;

    public function __construct()
    {

    }

    public function getAvailableCities()
    {
        $em = $this->em();
        $con = $em->getConnection();
        $cities = $con->fetchAll("SELECT DISTINCT(pp.city) FROM `place_point` pp, `place` p WHERE pp.place = p.id AND pp.active=1 AND p.active = 1");
        foreach ($cities as &$city) {
            $city = $city['city'];
        }
        return $cities;
    }
}