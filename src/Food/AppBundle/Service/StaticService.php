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
}