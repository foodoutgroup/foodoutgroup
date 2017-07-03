<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Food\AppBundle\Entity\City;

class BestOfferRepository extends EntityRepository
{

    /**
     * @param string|null $city
     * @param boolean $forMobile
     * @return array|BestOffer[]
     */
    public function getActiveOffers(City $city = null, $forMobile = false)
    {


        $cityService = $this->getEntityManager()->getRepository('FoodAppBundle:City');
        $params = ['active' => 1];
        if ($forMobile) {
            $params['useUrl'] = false;
        }
        $bestOffers = $this->findBy($params);

        if (!empty($city)) {

            if(is_string($city)){

                $cityObj =  $cityService->findOneBy(['title'=>$city]);
                $city = $cityObj->getId();
            }

            foreach ($bestOffers as $key => $offer) {
                $checker = false;
                foreach ($offer->getOfferCity() as $city_val) {

                    if ($city_val->getId() == $city->getId()) {
                        $checker = true;
                    }
                }
                if ($checker === false) {
                    unset($bestOffers[$key]);
                }
            }
        }

        return $bestOffers;
    }

    /**
     * @param $element
     * @return mixed
     */
    private function filterIds($element)
    {
        return $element['id'];
    }

    public function getBestOffersByIds($ids)
    {
        $result = $this->findBy(['id' => $ids]);

        return $result;
    }
}
