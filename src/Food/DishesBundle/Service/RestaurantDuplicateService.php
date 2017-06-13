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

        $oldPlace = $placeRepo->find($placeId);
        $newPlace = $oldPlace;
        $name = $newPlace->getName() . '-duplicate';
        $slug = $newPlace->getSlug() . '-' . $newPlace->getId() . uniqid();

        $newPlace->setId(null);
        $newPlace->setActive(0);
        $newPlace->setName($name);
        $newPlace->setSlug($slug);

        //kitchens

        $kitchenRel = $placeRepo->getRelatedKitchens($placeId);
        if (!empty($kitchenRel)) {
            foreach ($kitchenRel as $kitchen) {
                $newPlace->addKitchen($kitchenRepo->find($kitchen['kitchen_id']));
            }
        }

        $em->detach($newPlace);
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
              $em->flush();
        }


        //seo

        $seoRel = $placeRepo->getRelatedSeoRecords($placeId);

        if (!empty($seoRel)) {

            foreach ($seoRel as $record) {
                $seoItem = $seoRepo->find($record['seorecord_id']);
                $seoItem->addPlace($oldPlace);
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

        $oldDishes = $dishRepo->findBy(['place'=>$placeId]);

        if (!empty($oldDishes)) {
            foreach ($oldDishes as $key => $dish) {
                 $oldDishes[$key]->setPlace($placeRepo->find($newPlace->getId()));
                $em->persist($oldDishes[$key]);
            }
        }




        $em->flush();


        return $newPlace->getId();


    }


}