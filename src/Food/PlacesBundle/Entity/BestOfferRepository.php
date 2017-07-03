<?php

namespace Food\PlacesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BestOfferRepository extends EntityRepository
{

    /**
     * @param string|null $city
     * @param boolean $forMobile
     * @return array|BestOffer[]
     */
    public function getActiveOffers($city = null, $forMobile = false)
    {

        $bestOffers = $this->findBy(['active' => 1, 'useUrl' => $forMobile ? true : false]);
        $cityService = $this->getEntityManager()->getRepository('FoodAppBundle:City');

        if (!empty($city)) {

            if(is_string($city)){

                $cityObj =  $cityService->findOneBy(['title'=>$city]);
                $city = $cityObj->getId();
            }

            foreach ($bestOffers as $key => $offer) {
                $checker = false;
                foreach ($offer->getOfferCity() as $city_val) {

                    if ($city_val->getId() == $city) {
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
