<?php

namespace Food\DishesBundle\Utils;


use Food\DishesBundle\Utils\Slug\FoodCategoryStrategy;
use Food\DishesBundle\Utils\Slug\SlugGenerator;
use Food\DishesBundle\Utils\Slug\TextStrategy;
use Food\AppBundle\Entity;
use Food\AppBundle\Entity\Slug as SlugEntity;
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


    public function getSlugByItem($itemId, $type)
    {
        $item = $this->repo('FoodAppBundle:Slug')->findOneBy(
            array('item_id' => $itemId, 'type' => $type, 'lang_id' => $this->service('food.app.utils.locale')->getLocale())
        );

        if ($item) {
            return $item->getName();
        } else {
            return "error";
        }
    }

    public function getOneByName($slug)
    {
        // @todo - Kaip su keshu? Fisho kodas
        // $memcache = $this->service('beryllium_cache');

        //if (($item = $memcache->get('slug_' . $slug)) != null) return $item;

        $item = $this->repo('FoodAppBundle:Slug')->findOneByName($slug);
        // $memcache->set('slug_' . $slug, $item);

        return $item;
    }


    /**
     * Convert string to slug.
     *
     * @param   string  $text
     *
     * @return  string
     */
    public function stringToSlug($text)
    {
        $text = preg_replace('#\s+#u', '-', $text);
        return $text;
    }

    public function generateForKitchens($langId, $itemId, $itemText)
    {
        $strategy = new TextStrategy($this->container());
        $strategy->setType(SlugEntity::TYPE_KITCHEN);
        $context = new SlugGenerator($strategy);
        $context->generate($langId, $itemId, $itemText);
    }

    public function generateEntityForPlace($langId, $itemId, $itemText)
    {
        $strategy = new TextStrategy($this->container());
        $strategy->setType(SlugEntity::TYPE_PLACE);
        $context = new SlugGenerator($strategy);
        $context->generate($langId, $itemId, $itemText);
    }

    public function generateForFoodCategory($langId, $itemId, $itemText)
    {
        $context = new SlugGenerator(new FoodCategoryStrategy($this->container()));
        $context->generate($langId, $itemId, $itemText);
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
