<?php

namespace Food\DishesBundle\Service;

use Food\AppBundle\Entity\Slug;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlaceLocalized;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;
use Symfony\Component\Validator\Constraints\Null;

class RestaurantDuplicateService extends ContainerAware
{
    use Traits\Service;

    public function __construct()
    {

    }

    public function DuplicateRestaurant($placeId)
    {
        $em = $this->container->get('doctrine')->getManager();
        $placeRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place');
        $dishRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Dish');
        $seoRepo = $this->container->get('doctrine')->getRepository('FoodAppBundle:SeoRecord');
        $slugRepo = $this->container->get('doctrine')->getRepository('FoodAppBundle:Slug');
        $kitchenRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Kitchen');
        $dishSizeRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:DishSize');

        $oldPlace = $placeRepo->find($placeId);
        $newPlace = clone $oldPlace;
        $name = $newPlace->getName() . '-duplicate';
        $slug = $newPlace->getSlug() . '-' . $newPlace->getId() . uniqid();

        $newPlace->setId(null);
        $newPlace->setActive(0);
        $newPlace->setName($name);
        $newPlace->setSlug($slug);

        $em->persist($newPlace);
        $em->flush();

        foreach ($oldPlace->getTranslations() as $translation) {
            $transRecord = new PlaceLocalized();
            $transRecord->setContent($translation->getContent());
            $transRecord->setLocale($translation->getLocale());
            $transRecord->setField($translation->getField());
            $transRecord->setObject($newPlace);
            if ($translation->getField() == 'slug') {
                $transRecord->setContent($slug);
            }
            $em->persist($transRecord);
        }


        //seo

        $seoRel = $placeRepo->getRelatedSeoRecords($placeId);

        if (!empty($seoRel)) {

            foreach ($seoRel as $record) {
                $seoItem = $seoRepo->find($record['seorecord_id']);
                $seoItem->addPlace($newPlace);
                $em->persist($seoItem);
            }
        }

        //common slugs

        $commonSlugs = $slugRepo->findBy(['type' => 'place', 'item_id' => $placeId]);

        if (!empty($commonSlugs)) {
            foreach ($commonSlugs as $slugItem) {
                $newSlug = new Slug();
                $newSlug->setItemId($newPlace->getId());
                $newSlug->setName($slug);
                $newSlug->setOrigName($slug);
                $newSlug->setActive(1);
                $newSlug->setType('place');
                $newSlug->setLangId($slugItem->getLangId());
                $em->persist($newSlug);
            }
        }

        //dishes

        $oldDishes = $dishRepo->findBy(['place' => $placeId]);

        if (!empty($oldDishes)) {
            foreach ($oldDishes as $key => $dish) {
                $newDish = clone $dish;
                $newDish->setId(null);
                $newDish->setPlace($newPlace);
                $em->persist($newDish);

                foreach ($dish->getTranslations() as $translation) {
                    $newTranslation = clone $translation;
                    $newTranslation->setId(null);
                    $newTranslation->setObject($newDish);
                    $em->persist($newTranslation);

                }

                $dishSize = $dishSizeRepo->findBy(['dish' => $dish]);

                foreach ($dishSize as $size) {
                    $cloneSize = clone $size;
                    $cloneSize->setId(null);
                    $cloneSize->setDish($newDish);
                    $em->persist($cloneSize);
                }

                foreach ($dish->getOptions() as $option) {
                    var_dump($option);
                    echo '<br>----------------------- end';
                    $cloneOption = clone $option;
                    $cloneOption->setId(null);
                    $cloneOption->setPlace($newPlace);
                    $em->persist($cloneOption);

//                    foreach ($option->getTranslations() as $optionTranslation) {
//                        $newOptionTranslation = clone $optionTranslation;
//                        $newOptionTranslation->setObject($cloneOption);
//                        $newOptionTranslation->setId(null);
//                        $em->persist($newOptionTranslation);
//                    }
                }
die;
            }

        }

        $em->flush();

        return $newPlace->getId();
    }


}