<?php

namespace Food\DishesBundle\Utils\Slug;

use Food\AppBundle\Traits;
use Food\AppBundle\Entity\Slug;


class FoodCategoryStrategy extends AbstractStrategy
{
    use Traits\Service;

    const BATCH_SIZE = 1000;

    private $type = Slug::TYPE_FOOD_CATEGORY;

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
        // services
        $em = $this->em();
        $con = $em->getConnection();
        $slugUtil = $this->service('food.dishes.utils.slug');

        $logger = $this->service('logger');

        $slugs = $this->getSlugs($langId);
        $slug = $this->makeSlug($langId, $text);

        $slugPart = $this->getPlaceSlug($textId, $langId);
        $slug = $slugPart.'/'.$slug;
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
        $id = $this->idExistsIn($slugs, $textId);
        if ($id && $id > 0) {
            $item = $em->getRepository('FoodAppBundle:Slug')
                ->find($id);
        } else {
            $item = new Slug();
        }


        $item
            ->setItemId($textId)
            ->setLangId($langId)
            ->setType($this->getType())
            ->setName($slug)
            ->setOrigName($origSlug)
            ->setIsActive(1);


        $em->persist($item);
        $em->flush();

    }


    /**
     * @param $categoryId
     * @param $langId
     */
    private function getPlaceSlug($categoryId, $langId)
    {
        $em = $this->em();
        $row = $em->getRepository('FoodDishesBundle:FoodCategory')->findOneById($categoryId);
        $placeRow = $em->getRepository('FoodAppBundle:Slug')->findOneBy(array('item_id' => $row->getPlace()->getId(), 'type' => 'place', 'lang_id' => $langId));
        return $placeRow->getName();
    }

    private function idExistsIn($slugs, $id)
    {
        foreach ($slugs as $slRow) {
            if ($slRow['item_id'] == $id) {
                return $slRow['id'];
            }
        }
        return false;
    }

    /**
     * @param $slugs
     * @param $slug
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
        $slugUtil = $this->service('food.dishes.utils.slug');
        $languageUtil = $this->service('food.app.utils.language');
        return $slugUtil->stringToSlug(
            $languageUtil->removeChars($lang, $text)
        );
    }
}
