<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Traits;

class StaticService {
    use Traits\Service;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var int
     */
    private $userId;

    /**
     * @param Container $container
     * @param integer $userId
     */
    public function __construct($container, $userId)
    {
        $this->container = $container;
        $this->userId = $userId;
    }

    /**
     * @param \Food\AppBundle\Service\Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Food\AppBundle\Service\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Get static page by id
     *
     * @param null|integer $id
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function getPage($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Sorry, no ID - no information. Get lucky!');
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $staticPage = $em->getRepository('Food\AppBundle\Entity\StaticContent')->find($id);

        if (!$staticPage) {
            return false;
        }

        return $staticPage;
    }

    /**
     * @param int $limit
     * @param boolean $onlyVisible
     * @return array
     */
    public function getActivePages($limit=10, $onlyVisible=true)
    {
        $pagesQueryBuilder = $this->em()->getRepository('FoodAppBundle:StaticContent')
            ->createQueryBuilder('s')
        // TODO active-not active ir positioning (top, bottom menu, hidden)
            ->where('s.active = 1')
            ->orderBy('s.order', 'ASC')
            ->setMaxResults($limit);

        if ($onlyVisible) {
            $pagesQueryBuilder->andWhere('s.visible = 1');
        }

        $pagesQuery = $pagesQueryBuilder->getQuery();

        return $pagesQuery->getResult();
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
        $stmt = $this->em()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
