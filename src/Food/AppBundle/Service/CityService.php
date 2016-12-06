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
    public function getCityInfo($cityString)
    {
        $cityInfo = array();
        $availableCitiesSlugs = array_map("mb_strtolower", $this->availableCitiesSlugs);

        $cityString = $this->language->removeChars($this->locale, $cityString, true, false);
        $city = str_replace(array("#", "-",";","'",'"',":", ".", ",", "/", "\\"), "", ucfirst($cityString));
        $cityInfo['city_slug_lower'] = strtolower($city);

//        if (!empty($city) && in_array(mb_strtolower($city), $availableCitiesSlugs)) {
//            $city_url = $this->router->generate('food_city_' . lcfirst($city), [], true);
//        } else {
//            $city_name = lcfirst(reset($availableCitiesSlugs));
//            $city = ucfirst($city_name);
//            $city_url = $this->router->generate('food_city_' . (!empty($city_name) ? $city_name : 'vilnius'), [], true);
//        }
        $city_url = '';
        $cityInfo['city_url'] = $city_url;
        $cityInfo['city'] = $city;

        return $cityInfo;
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
        return $this->em->getRepository('FoodAppBundle:City')->findOneBy(null, ['position' => 'ASC']);
    }

    public function getCityById($cityId)
    {
        return $this->em->getRepository('FoodAppBundle:City')->findOneBy(['id' => $cityId]);
    }

    public function getCityBySlug($slug)
    {
        return $this->em->getRepository('FoodAppBundle:City')->findOneBy(['slug' => $slug]);
    }


}