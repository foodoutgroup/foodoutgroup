<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
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

        $staticPage = $this->getContainer()->get('doctrine')->getRepository('Food\AppBundle\Entity\StaticContent')->find($id);

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

        $excludePageCollection = [];
        $keywordMapCollection = [
            'page_banned',
            'page_email_banned',
            'page_sitemap'
        ];

        /**
         * @var $paramService \Food\AppBundle\Utils\Misc
         */
        $paramService = $this->getContainer()->get('food.app.utils.misc');
        foreach ($keywordMapCollection as $keyword) {

            $data = (int)$paramService->getParam($keyword, true);
            if($data != 0) {
                $excludePageCollection[] = $data;
            }
        }


        /**
         * $qb Doctrine\ORM\EntityManager
         */
        $qb = $this->em()->getRepository('FoodAppBundle:StaticContent')
            ->createQueryBuilder('s');

        $pagesQueryBuilder = $qb->where('s.active = 1');
        if (count($excludePageCollection)) {
            $pagesQueryBuilder->andWhere($qb->expr()->notIn('s.id', $excludePageCollection));
        }
        $pagesQueryBuilder->orderBy('s.order', 'ASC')
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
     * @deprecated from 2017-04-26
     */
    public function getPlacesWithOurLogistic()
    {
       return [];
    }
}
