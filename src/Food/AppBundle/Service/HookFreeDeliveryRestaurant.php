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
     * @param bool $cities
     * @return mixed
     */
    public function getPlacesWithOurLogistic($cities = false)
    {
        if ($cities) {
            $fields = "distinct(pp.city)";
            $group = "1";
        } else {
            $fields = "pp.city, p.id, p.name";
            $group = "1, 2";
        }
        $query = "
            SELECT " . $fields . "
            FROM place_point pp, place p
            WHERE pp.place = p.id
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
        return $this->templating->render("@FoodApp/Hook/free_delivery_restaurant.html.twig", [
            'cities' => $this->getPlacesWithOurLogistic(true),
            'places' => $this->getPlacesWithOurLogistic()
        ]);
    }
}
