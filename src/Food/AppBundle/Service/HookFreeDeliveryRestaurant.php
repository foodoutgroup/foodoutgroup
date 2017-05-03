<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class HookFreeDeliveryRestaurant {

    private $templating;
    private $em;

    public function __construct(EngineInterface $templating, EntityManager $entityManager)
    {
        $this->templating = $templating;
        $this->em = $entityManager;
    }

    /**
     * @param bool $distinct
     * @return mixed
     */
    public function getPlacesWithOurLogistic($distinct = false)
    {

        $cityCollection = $this->em->getRepository('FoodAppBundle:City')->getActive();

        $dataCollection = [];
        foreach ($cityCollection as $city) {

        }


        if ($distinct) {
            $fields = "distinct(pp.city_id)";
            $group = "1";
        } else {
            $fields = "c.name, p.id, p.name";
            $group = "1, 2";
        }
        $query = "
            SELECT " . $fields . "
            FROM place_point pp, place p, city c
            WHERE pp.place = p.id
            AND pp.cityId = c.id
            AND p.self_delivery = 0
            AND pp.active = 1
            AND p.active = 1
            GROUP BY " . $group
        ;
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function build()
    {

        $cityCollection = $this->em->getRepository('FoodAppBundle:City')->getActive();
        $dataCollection = [];
        foreach ($cityCollection as $city) {

            $qb = $this->em->createQueryBuilder()
                ->select('pp')
                ->from('FoodDishesBundle:PlacePoint', 'pp')
                ->innerJoin('pp.place', 'p', 'pp.place = p.id')
                ->where('pp.active = 1')
                ->andWhere('p.active = 1')
                ->andWhere('p.selfDelivery = 1')
                ->andWhere('pp.cityId = :cityId');

            $qb->setParameter('cityId', $city->getId());

            $dataCollection[] = [
                'city' => $city,
                'collection' => $qb->getQuery()->execute()
            ];
        }

        return $this->templating->render("@FoodApp/Hook/free_delivery_restaurant.html.twig", [
            'dataCollection' => $dataCollection,
        ]);
    }
}
