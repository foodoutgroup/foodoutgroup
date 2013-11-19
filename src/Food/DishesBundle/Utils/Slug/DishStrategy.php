<?php

namespace Food\DishesBundle\Utils\Slug;

use Food\DishesBundle\Utils\Slug\AbstractStrategy;
// use Fish\CommonBundle\Traits;
// use Fish\CommonBundle\Entity\Slug;
// use Axelarge\ArrayTools\Arr;

class DishStrategy extends AbstractStrategy
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
        $products = $this->getProducts($language->getId());
        $brands = $this->getBrands($language->getId());

        foreach ($slugs as $row) {
            $existing[$row[0]->getItemId()] = true;
        }

        // create new slugs
        $con->beginTransaction();

        try {
            foreach ($products as $id => $product) {
                if (!empty($existing[$id])) continue;

                $slug = $this->makeSlug($brands[$product['c_p_brand_id']]['c_b_name'], $product['c_pd_name'], $product['c_pd_variation_name']);
                $newSlug = $slugUtil->getFinalSlugName($pool, $slug);

                $item = new Slug();
                $item
                    ->setItemId($id)
                    ->setLangId($language->getId())
                    ->setType('product')
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
            $logger->err('Cannot generate slugs for products.', ['exception' => $e]);
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
            ->setParameters(['type' => 'product', 'language' => $language->getId()])
            ->getQuery()
            ->iterate();
    }

    private function getProducts($langId)
    {
        return Arr::pluck(Arr::groupBy($this->em()
            ->createQueryBuilder()
            ->select('c_p, c_pd')
            ->from('FishCommonBundle:Product', 'c_p')
            ->innerJoin('FishCommonBundle:ProductDescription', 'c_pd', 'WITH', 'c_pd.product_id = c_p.id AND c_pd.lang_id = :language')
            ->groupBy('c_p.id')
            ->setParameters(['language' => $langId])
            ->getQuery()
            ->getScalarResult(), 'c_p_id'), 0);
    }

    private function getBrands($langId)
    {
        return Arr::pluck(Arr::groupBy($this->em()
            ->createQueryBuilder()
            ->select('c_b')
            ->from('FishCommonBundle:Brand', 'c_b')
            ->innerJoin('FishCommonBundle:BrandDescription', 'c_bd', 'WITH', 'c_bd.brand_id = c_b.id AND c_bd.lang_id = :language')
            ->setParameters(['language' => $langId])
            ->getQuery()
            ->getScalarResult(), 'c_b_id'), 0);
    }

    private function makeSlug($brand, $name, $variationName)
    {
        $slugUtil = $this->service('fish.parado.utils.slug');

        $brand = mb_strtolower($brand, 'utf-8');
        $name = mb_strtolower($name, 'utf-8');
        $variationName = mb_strtolower($variationName, 'utf-8');

        $slugs = [];

        if (!empty($brand) && $brand != '-') $slugs[] = $brand;
        if (!empty($name) && $name != '-') $slugs[] = $name;
        if (!empty($variationName) && $variationName != '-') $slugs[] = $variationName;

        return $slugUtil->stringToSlug(
            iconv(
                'utf-8',
                'us-ascii//TRANSLIT',
                implode(' ', $slugs)
            )
        );
    }
}
