<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
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
        $this->locale= $this->request->getLocale();
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