<?php

namespace Food\DishesBundle\Utils\Slug;

use Food\AppBundle\Traits;
use Food\AppBundle\Entity\Slug;


class TextStrategy extends AbstractStrategy
{
    use Traits\Service;

    const BATCH_SIZE = 1000;

    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        $this->container($container);
    }

    /**
     * @param $langId
     * @param $textId
     * @param $text
     */
    public function generate($langId, $textId, $text)
    {
        // services
        $em = $this->em();
        $con = $em->getConnection();
        $slugUtil = $this->service('food.dishes.utils.slug');

        $logger = $this->service('logger');

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

        if ($this->idExistsIn($slugs, $textId)) {
            $item = $em->getRepository('FoodAppBundle:Slug')
                ->find($this->idExistsIn($slugs, $textId));
        } else {
            $item = new Slug();
        }


        $item
            ->setItemId($textId)
            ->setLangId($langId)
            ->setType('text')
            ->setName($slug)
            ->setOrigName($origSlug)
            ->setIsActive(1);


        $em->persist($item);
        $em->flush();

    }


    private function idExistsIn($slugs, $id)
    {
        foreach ($slugs as $slRow) {
            if ($slRow['item_id'] == $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $slugs
     * @param $slug
     * @return bool
     */
    private function existsInOrigs($slugs, $slug)
    {
        foreach ($slugs as $slRow) {
            if ($slRow['orig_name'] == $slug) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $slugs
     * @param $slug
     */
    private function existsIn($slugs, $slug)
    {
        foreach ($slugs as $slRow) {
            if ($slRow['name'] == $slug) {
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
            ->setParameters(['type' => 'text', 'language' => $langId])
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
        $slugUtil = $this->service('food.dishes.utils.slug');
        $languageUtil = $this->service('food.app.utils.language');
        return $slugUtil->stringToSlug(
            $languageUtil->removeChars($lang, $text)
        );
    }
}
