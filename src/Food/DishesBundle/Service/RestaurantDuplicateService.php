<?php

namespace Food\DishesBundle\Service;

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

        $kitchenRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Kitchen');

        $oldPlace = $placeRepo->find($placeId);

        $newPlace = $oldPlace;
        $newPlace->setId(null);
        $newPlace->setActive(0);
        $newPlace->setName($newPlace->getName() . '-duplicate');
        $slug = $newPlace->getSlug() . '-' . $newPlace->getId() . uniqid();
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

        foreach ($oldPlace->getTranslations() as $translation){
            $transRecord = new PlaceLocalized();
            $transRecord->setContent($translation->getContent());
            $transRecord->setLocale($translation->getLocale());
            $transRecord->setField($translation->getField());
            $transRecord->setObject($newPlace);
            if($translation->getField() == 'slug'){
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
                $em->persist($transRecord);
            }
        }

        $em->flush();



        return $newPlace->getId();



    }


}