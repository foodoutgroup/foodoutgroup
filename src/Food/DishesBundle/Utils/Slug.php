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
    use Traits\Service;

    private $slug;

    /**
     * Crazy magic :)
     *
     * @var string
     */
    private $locale;

    /**
     * The epic magic
     *
     * @var string
     */
    private $defLocale;

    public function __construct($defLocale = "en")
    {
        $this->defLocale = $defLocale;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->container()->get('request');
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }


    /**
     * @return string
     */
    public function getLocale()
    {
        if (empty($this->locale))
        {
            $loc = $this->getRequest()->getLocale();
            if (empty($loc)) {
                $loc = $this->defLocale;
            }
            $this->setLocale($loc);
        }

        return $this->locale;
    }


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
            array('item_id' => $itemId, 'type' => $type, 'lang_id' => $this->getLocale())
        );

        if ($item) {
            return $item->getName();
        } else {
            return "error";
        }
    }

    public function getOneByName($slug, $lang)
    {
        $item = $this->repo('FoodAppBundle:Slug')->findOneBy(['name' => $slug, 'lang_id' => $lang ]);
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
        $removableChars = array(
            '"' => '',
            '„' => '',
            '“' => '',
            '+' => '-',
            '(' => '',
            ')' => '',
            '.' => '',
        );
        $text = strtr($text, $removableChars);
        $text = preg_replace('#\s+#u', '-', $text);

        $text = preg_replace('~-{2,}~', '-', $text);

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
        $strategy = new FoodCategoryStrategy($this->container());
        $strategy->setContainer($this->container());
        $context = new SlugGenerator($strategy);
        $context->generate($langId, $itemId, $itemText);
    }

    public function generateForTexts($langId, $itemId, $itemText)
    {
        $context = new SlugGenerator(new TextStrategy($this->container()));
        $context->generate($langId, $itemId, $itemText);
    }

    /**
     * TODO Not working - fatal injuries can be caused
     */
    public function fixUppercaseSlugs()
    {
        $em = $this->em();
        $con = $em->getConnection();
//        $repo =

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
