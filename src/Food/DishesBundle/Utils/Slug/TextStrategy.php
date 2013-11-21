<?php

namespace Food\DishesBundle\Utils\Slug;

use Food\DishesBundle\Utils\Slug\AbstractStrategy;
use Food\AppBundle\Traits;
use Food\AppBundle\Entity\Slug;
use Axelarge\ArrayTools\Arr;

class TextStrategy extends AbstractStrategy
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
        $slugUtil = $this->service('food.dishes.utils.slug');
        $languageUtil = $this->service('food.app.utils.language');
        $logger = $this->service('logger');

        // init vars
        $i = 0;
        $language = $languageUtil->getById($langId);
        $existing = [];
        $pool = [];

        // we will use these lists later
        $slugs = $this->getSlugs($language->getId());
        $texts = $this->getTexts($language->getId());

        foreach ($slugs as $row) {
            $existing[$row[0]->getItemId()] = true;
        }

        // create new slugs
        $con->beginTransaction();

        try {
            foreach ($texts as $id => $text) {
                if (!empty($existing[$id])) continue;

                $slug = $this->makeSlug($text['m_td_title']);
                $newSlug = $slugUtil->getFinalSlugName($pool, $slug);

                $item = new Slug();
                $item
                    ->setItemId($id)
                    ->setLangId($language->getId())
                    ->setType('text')
                    ->setName($newSlug)
                    ->setIsActive(1);
                $em->persist($item);

                $pool[] = $newSlug;

                if ((++$i % static::BATCH_SIZE) == 0) $em->flush();
            }

            $em->flush();
            $con->commit();
        } catch (\Exception $e) {
            $con->rollback();
            $logger->err('Cannot generate slugs for text pages.', ['exception' => $e]);
        }
    }

    private function getSlugs($langId)
    {
        $languageUtil = $this->service('food.app.utils.language');

        $language = $languageUtil->getById($langId);

        return $this->em()
            ->createQueryBuilder()
            ->select('c_s')
            ->from('FoodAppBundle:Slug', 'c_s')
            ->where('c_s.type = :type')
            ->andWhere('c_s.lang_id = :language')
            ->setParameters(['type' => 'text', 'language' => $language->getId()])
            ->getQuery()
            ->iterate();
    }

    private function getTexts($langId)
    {
        $languageUtil = $this->service('food.app.utils.language');

        $language = $languageUtil->getById($langId);

        return Arr::pluck(Arr::groupBy($this->em()
            ->createQueryBuilder()
            ->select('m_t, m_td')
            ->from('ManageBundle:Text', 'm_t')
            ->innerJoin('ManageBundle:TextDescription', 'm_td', 'WITH', 'm_td.text_id = m_t.id AND m_td.lang_id = :language')
            ->groupBy('m_td.id')
            ->setParameters(['language' => $language->getId()])
            ->getQuery()
            ->getScalarResult(), 'm_t_id'), 0);
    }

    private function makeSlug($product)
    {
        $slugUtil = $this->service('food.dishes.utils.slug');

        return $slugUtil->stringToSlug(
            iconv(
                'utf-8',
                'us-ascii//TRANSLIT',
                mb_strtolower($product, 'utf-8')
            )
        );
    }
}
