<?php

namespace Food\DishesBundle\Utils\Slug;

use Food\DishesBundle\Utils\Slug\AbstractStrategy;
use Food\AppBundle\Traits;
use Food\AppBundle\Entity\Slug;
use Axelarge\ArrayTools\Arr;

class PlaceStrategy extends AbstractStrategy
{
    use Traits\Service;

    const BATCH_SIZE = 1000;

    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        $this->container($container);
    }

    public function generate($langId)
    {
        // services
        $em = $this->em();
        $con = $em->getConnection();
        $slugUtil = $this->service('fish.parado.utils.slug');
        $languageUtil = $this->service('fish.common.utils.language');
        $logger = $this->service('logger');

        // init vars
        $i = 0;
        $language = $languageUtil->getById($langId);
        $existing = [];
        $pool = [];

        // we will use these lists later
        $slugs = $this->getSlugs($language->getId());
        $cats = $this->getCategories($language->getId());

        foreach ($slugs as $row) $existing[$row[0]->getItemId()] = true;

        // create new slugs
        $con->beginTransaction();

        try {
            foreach ($cats as $row) {
                if (!empty($existing[$row[0]->getId()])) continue;

                $slug = $this->makeSlug($row[0]->getPath());
                $newSlug = $slugUtil->getFinalSlugName($pool, $slug);

                $slug = new Slug($row[0]->getPath());
                $slug
                    ->setItemId($row[0]->getId())
                    ->setLangId($language->getId())
                    ->setType('category')
                    ->setName($newSlug)
                    ->setIsActive(1);
                $em->persist($slug);

                $pool[] = $newSlug;

                if ((++$i % static::BATCH_SIZE) == 0) $em->flush();
            }

            $em->flush();
            $con->commit();
        } catch (\Exception $e) {
            $con->rollback();
            $logger->err('Cannot generates slugs for categories.', ['exception' => $e]);
        }
    }

    private function getSlugs($langId)
    {
        $languageUtil = $this->service('fish.common.utils.language');

        $language = $languageUtil->getById($langId);

        return $this->em()
            ->createQueryBuilder()
            ->select('c_s')
            ->from('FishCommonBundle:Slug', 'c_s')
            ->where('c_s.type = :type')
            ->andWhere('c_s.lang_id = :language')
            ->setParameters(['type' => 'category', 'language' => $language->getId()])
            ->getQuery()
            ->iterate();
    }

    private function getCategories($langId)
    {
        return $this->em()
            ->createQueryBuilder()
            ->select('p_c')
            ->from('FishParadoBundle:Category', 'p_c')
            ->innerJoin('FishParadoBundle:CategoryDescription', 'p_cd', 'WITH', 'p_cd.category_id = p_c.id AND p_cd.lang_id = :language')
            ->setParameters(['language' => $langId])
            ->getQuery()
            ->iterate();
    }

    private function makeSlug($path)
    {
        $slugUtil = $this->service('fish.parado.utils.slug');
        $repo = $this->repo('FishParadoBundle:Category');

        $catNames = [];

        foreach (array_filter(explode('/', $path)) as $id) {
            $catNames[] = $slugUtil->stringToSlug(
                iconv(
                    'utf-8',
                    'us-ascii//TRANSLIT',
                    mb_strtolower($repo->findOneById($id)->getDescription()->getName(), 'utf-8')
                )
            );
        }

        return implode('/', $catNames);
    }
}
