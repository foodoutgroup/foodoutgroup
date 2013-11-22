<?php

namespace Food\DishesBundle\Utils;


use Food\DishesBundle\Utils\Slug\SlugGenerator;
use Food\DishesBundle\Utils\Slug\BrandStrategy;
use Food\DishesBundle\Utils\Slug\CategoryStrategy;
use Food\DishesBundle\Utils\Slug\DishStrategy;
use Food\DishesBundle\Utils\Slug\TextStrategy;
use Food\AppBundle\Entity;
use Food\AppBundle\Traits;

class Slug
{
    //use Traits\Service;
    use Traits\Service;

    private $slug;
    private $mainSlug;

    public function set($slug)
    {
        $this->slug = $slug;
    }

    public function get()
    {
        return !empty($this->slug) ? $this->slug : '';
    }

    public function setMain($slug)
    {
        $this->mainSlug = $slug;
    }

    public function getMain()
    {
        return !empty($this->mainSlug) ? $this->mainSlug : '';
    }

    public function getFirstMainSlug()
    {
        $slug = Arr::getNested($this->em()
            ->createQueryBuilder()  
            ->select('c_s.name')
            ->from('FishCommonBundle:Slug', 'c_s')
            ->innerJoin('FishParadoBundle:Category', 'p_c', 'WITH', 'p_c.id = c_s.item_id')
            ->where('p_c.parent_id IS NULL')
            ->andWhere('c_s.type = :slug_type')
            ->andWhere('p_c.is_visible = :visible')
            ->orderBy('p_c.priority', 'ASC')
            ->setParameter('slug_type', 'category')
            ->setParameter('visible', 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getScalarResult(), '0.name', '');

        return $slug;
    }

    public function getOneByName($slug)
    {
        $memcache = $this->service('beryllium_cache');

        if (($item = $memcache->get('slug_' . $slug)) != null) return $item;

        $item = $this->repo('FishCommonBundle:Slug')->findOneByName($slug);
        $memcache->set('slug_' . $slug, $item);

        return $item;
    }

    public function getTopCategorySlug($slug)
    {
        $em = $this->em();
        $slugInfo = $this->repo('FishCommonBundle:Slug')->findOneBy(['name' => $slug]);
        $path = '';

        if (empty($slugInfo)) return '';

        $type = $slugInfo->getType();
        if ($type == 'product') $path = $this->getPathByProductId($slugInfo->getItemId());
        elseif ($type == 'category') $path = $this->getPathByCategoryId($slugInfo->getItemId());

        if (empty($path)) return '';

        $list = explode('/', $path);
        $parentId = array_shift($list);
        $slug = $em
            ->createQueryBuilder()
            ->select('c_s.name')
            ->from('FishCommonBundle:Slug', 'c_s')
            ->where('c_s.item_id = :category')
            ->andWhere('c_s.type = :slug_type')
            ->andWhere('c_s.is_active = 1')
            ->setParameters(['category' => $parentId, 'slug_type' => 'category'])
            ->getQuery()
            ->getScalarResult();

        return !empty($slug[0]) ? $slug[0]['name'] : '';
    }

    private function getPathByProductId($id)
    {
        $memcache = $this->service('beryllium_cache');

        if (($path = $memcache->get('path_by_product_id_' . $id)) != null) return $path;

        $category = $this->em()
            ->createQueryBuilder()
            ->select('p_c.path')
            ->from('FishParadoBundle:Category', 'p_c')
            ->innerJoin('FishParadoBundle:ProductToCategory', 'p_p2c', 'WITH', 'p_p2c.category_id = p_c.id')
            ->where('p_p2c.product_id = :product')
            ->setParameters(['product' => $id])
            ->getQuery()
            ->getScalarResult();

        $path = !empty($category[0]) ? $category[0]['path'] : '';
        $memcache->set('path_by_product_id_' . $id, $path);

        return $path;
    }

    private function getPathByCategoryId($id)
    {
        $memcache = $this->service('beryllium_cache');

        if (($path = $memcache->get('path_by_category_id_' . $id)) != null) return $path;

        $category = $this->em()
            ->createQueryBuilder()
            ->select('p_c.path')
            ->from('FishParadoBundle:Category', 'p_c')
            ->innerJoin('FishCommonBundle:Slug', 'c_s', 'WITH', 'c_s.item_id = p_c.id')
            ->where('p_c.path LIKE :path')
            ->andWhere('c_s.type = :slug_type')
            ->andWhere('c_s.is_active = 1')
            ->setParameters(['path' => '%' . $id . '/', 'slug_type' => 'category'])
            ->getQuery()
            ->getScalarResult();

        $path = !empty($category[0]) ? $category[0]['path'] : '';
        $memcache->set('path_by_category_id_' . $id, $path);

        return $path;
    }

    /**
     * Convert string to slug.
     * 
     * @param   string  $text
     * @param   boolean $skipUCWords
     * 
     * @return  string
     */
    public function stringToSlug($text)
    {
        $text = preg_replace('#\s+#u', '-', $text);
        return $text;
    }

    /**
     * Get real slug name.
     * Check database slugs table for duplicate slug names,
     * adds '-1', '-2', '-3' etc postfixes when needed.
     *
     * @param   array   $pool
     * @param   string  $slug
     * 
     * @return  string
     */
    public function getFinalSlugName(&$pool, $slug)
    {
        $repo = $this->repo('FishCommonBundle:Slug');
        $counter = 2;
        $postfix = '';
        
        while(($row = $repo->findOneByName($slug . $postfix)) != null) { $postfix = '-' . ($counter++); }
        while (in_array($slug . $postfix, $pool)) $postfix = '-' . ($counter++);

        if ($counter - 2 > 0) return ($slug . $postfix);
        else return $slug;
    }

    public function generateForBrands($langId)
    {
        $context = new SlugGenerator(new BrandStrategy($this->container()));
        $context->generate($langId);
    }

    public function generateForCategories($langId)
    {
        $context = new SlugGenerator(new CategoryStrategy($this->container()));
        $context->generate($langId);
    }

    public function generateForProducts($langId)
    {
        $context = new SlugGenerator(new ProductStrategy($this->container()));
        $context->generate($langId);
    }

    public function generateForTexts($langId, $itemId, $itemText)
    {
        $context = new SlugGenerator(new TextStrategy($this->container()));
        $context->generate($langId, $itemId, $itemText);
    }

    public function fixUppercaseSlugs()
    {
        $em = $this->em();
        $con = $em->getConnection();
        $repo = 

        // get uppercase slugs
        $slugs = $con->fetchAll('
            SELECT cs.id, cs.name
            FROM common_slug cs
            WHERE cs.name REGEXP BINARY "[A-Z]"
        ');

        $em->transactional(function($em) use ($slugs) {
            // update slug names with lowercase versions
            foreach ($slugs as $slug) {
                $item = $this->repo('FishCommonBundle:Slug')->findOneBy(['id' => $slug['id']]);
                $item->setName(mb_strtolower($slug['name'], 'utf-8'));
            } $em->flush();
        });
    }
}
