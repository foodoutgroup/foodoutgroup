<?php

namespace Food\DishesBundle\Utils\Slug;

use Food\AppBundle\Traits;
use Food\AppBundle\Entity\Slug;


class TextStrategy extends AbstractStrategy
{
    use Traits\Service;

    const BATCH_SIZE = 1000;

    private $type = Slug::TYPE_TEXT;

    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        $this->container($container);
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $langId
     * @param $textId
     * @param $text
     */
    public function generate($langId, $textId, $text)
    {
        $em = $this->em();
        $createNew = true;

        $slugs = $this->getSlugs($langId);
        $slug = $this->makeSlug($langId, $text);
        $origSlug = $slug;

        if ($this->existsInOrigs($slugs, $slug, $textId)) {
            $cnt = 0;
            while ($this->existsIn($slugs, $slug, $textId)) {
                if ($cnt != 0) {
                    $slug = $origSlug.'-'.$cnt;
                }
                $cnt++;
            }
        }

        $oldItems = $em->getRepository('FoodAppBundle:Slug')
            ->findBy(array(
                'item_id' => $textId,
                'type' => $this->getType(),
                'lang_id' => $langId,
            ));

        if ($oldItems) {
            foreach ($oldItems as $oldItem) {
                if ($oldItem->getName() == $slug && $oldItem->getLangId() == $langId) {
                    $oldItem->setActive(true);
                    $createNew = false;
                } else if ($oldItem->getName() != $slug) {
                    $oldItem->setActive(false);
                }
                $em->persist($oldItem);
            }
        }

        if ($createNew) {
            $item = new Slug();
            $item
                ->setItemId($textId)
                ->setLangId($langId)
                ->setType($this->getType())
                ->setName($slug)
                ->setOrigName($origSlug)
                ->setActive(1);

            $em->persist($item);
        }

        $em->flush();
    }

    /**
     * @param $slugs
     * @param $slug
     * @param $textId
     * @return bool
     */
    private function existsInOrigs($slugs, $slug, $textId)
    {
        foreach ($slugs as $slRow) {
            if ($slRow['orig_name'] == $slug && $slRow['item_id'] != $textId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $slugs
     * @param $slug
     */
    private function existsIn($slugs, $slug, $textId)
    {
        foreach ($slugs as $slRow) {
            if ($slRow['name'] == $slug  && $slRow['item_id'] != $textId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $langId
     * @return mixed
     */
    private function getSlugs($langId)
    {
        return $this->em()
            ->createQueryBuilder()
            ->select('c_s')
            ->from('FoodAppBundle:Slug', 'c_s')
            ->where('c_s.type = :type')
            ->andWhere('c_s.lang_id = :language')
            ->setParameters(['type' => $this->getType(), 'language' => $langId])
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param $lang
     * @param $text
     * @return mixed
     */
    private function makeSlug($lang, $text)
    {
        if (is_numeric($text) && mb_strlen($text, 'UTF-8') <= 4) {
            switch ($this->type) {
                case Slug::TYPE_PLACE:
                    $text .= '-'.$this->container()->get('translator')->trans('general.restaurant', array(), null, $lang);
                    break;
                // @TODO: Other types
            }
        }
        $slugUtil = $this->service('food.dishes.utils.slug');
        $languageUtil = $this->service('food.app.utils.language');
        return $slugUtil->stringToSlug(
            $languageUtil->removeChars($lang, $text)
        );
    }
}