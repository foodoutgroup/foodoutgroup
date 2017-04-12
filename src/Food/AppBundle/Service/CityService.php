<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Utils\Language;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class CityService extends BaseService
{
    protected $router;
    protected $availableCitiesSlugs;
    protected $locale;
    protected $language;
    protected $request;

    public function __construct(EntityManager $em, Router $router, Language $language, $container)
    {
        parent::__construct($em);
        $this->router = $router;
        $this->language = $language;
        $this->request = $container->get('request');
    }

    /**
     * @param string $cityString
     * @return array
     */
    public function getCityInfo($cityId)
    {

        $cityInfoCollection = [];
        $cityObj = $this->em->getRepository('FoodAppBundle:City')->findOneBy(['id' => $cityId]);
        if($cityObj != null) {
            $cityString = $this->language->removeChars($this->locale, $cityObj->getTitle(), true, false);
            $city = str_replace(array("#", "-", ";", "'", '"', ":", ".", ",", "/", "\\"), "", ucfirst($cityString));
            $cityInfoCollection['city_slug_lower'] = strtolower($city);
            $cityInfoCollection['city_url'] = '';
            $cityInfoCollection['city'] = $city;
        }
        return $cityInfoCollection;
    }

    /**
     * @param mixed $availableCitiesSlugs
     */
    public function setAvailableCitiesSlugs($availableCitiesSlugs)
    {
        $this->availableCitiesSlugs = $availableCitiesSlugs;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
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

    public function getRandomBestOffers($cityId){

        $bestOfferIds = $this->em->getRepository('FoodAppBundle:City')->getBestOffersByCity($cityId);

        if (!empty($bestOfferIds)) {


            foreach ($bestOfferIds as $item) {
                $tmpOfferIds[] = $item['id'];
            }

            shuffle($tmpOfferIds);

            $bestOfferIds = array_slice($tmpOfferIds, 0, 5);

            $bestOffers = $this->em->getRepository('FoodPlacesBundle:BestOffer')->getBestOffersByIds($bestOfferIds);
        }else{
            $bestOffers = null;
        }
        return $bestOffers;

    }


}