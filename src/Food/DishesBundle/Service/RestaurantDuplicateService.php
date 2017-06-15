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
        $dishOptionRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:DishOption');
        $foodCategoryRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:FoodCategory');
        $dishUnitsCategoryRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:DishUnitCategory');
        $dishUnitRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:DishUnit');
        $comboDiscountRepo = $this->container->get('doctrine')->getRepository('FoodDishesBundle:ComboDiscount');

        $oldPlace = $placeRepo->find($placeId);
        $newPlace = clone $oldPlace;
        $name = $newPlace->getName() . '-duplicate';
        $slug = $newPlace->getSlug() . '-' . $newPlace->getId() . uniqid();

        $newPlace->setId(null);
        $newPlace->setActive(0);
        $newPlace->setName($name);
        $newPlace->setSlug($slug);

        $em->persist($newPlace);

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
                $newSlug->setItemId($newPlace);
                $newSlug->setName($slug);
                $newSlug->setOrigName($slug);
                $newSlug->setActive(1);
                $newSlug->setType('place');
                $newSlug->setLangId($slugItem->getLangId());
                $em->persist($newSlug);
            }
        }



        $dishOptions = $dishOptionRepo->findby(['place' => $placeId]);

        foreach ($dishOptions as $option) {

            $cloneOption = clone $option;
            $cloneOption->setId(null);
            $cloneOption->setPlace($newPlace);
            $em->persist($cloneOption);
            $translation = $option->getTranslations();

            if (!empty($translation)) {
                foreach ($translation as $optionTranslation) {
                    $newOptionTranslation = clone $optionTranslation;
                    $newOptionTranslation->setObject($cloneOption);
                    $newOptionTranslation->setId(null);
                    $em->persist($newOptionTranslation);
                }
            }
        }

        $foodCategories = $foodCategoryRepo->findBy(['place' => $placeId]);
        $foodCategoriesArray = [];
        foreach ($foodCategories as $foodCategory) {

            $cloneFoodCategory = clone $foodCategory;
            $cloneFoodCategory->setId(null);
            $cloneFoodCategory->setPlace($newPlace);
            $em->persist($cloneFoodCategory);
            $foodCategoriesArray[$foodCategory->getId()] = $cloneFoodCategory->getId();
            $translation = $option->getTranslations();
            if (!empty($translation)) {
                foreach ($translation as $FoodCategoryTranslation) {
                    $newFoodCategory = clone $FoodCategoryTranslation;
                    $newFoodCategory->setObject($cloneOption);
                    $newFoodCategory->setId(null);
                    $em->persist($newFoodCategory);
                }
            }
        }

        $dishUnitsCategories = $dishUnitsCategoryRepo->findBy(['place' => $placeId]);
        $dishUnitsCategoryArray = [];

        foreach ($dishUnitsCategories as $dishUnitCategory) {
            $cloneDishUnitCategory = clone $dishUnitCategory;
            $cloneDishUnitCategory->setId(null);
            $cloneDishUnitCategory->setPlace($newPlace);
            $em->persist($cloneDishUnitCategory);
            $dishUnitsCategoryArray[$dishUnitCategory->getId()] = $cloneDishUnitCategory;

        }

        $dishUnitsArray = [];

        $dishUnits = $dishUnitRepo->findBy(['place' => $placeId]);


        foreach ($dishUnits as $dishUnit) {


            $cloneDishUnit = clone $dishUnit;
            $cloneDishUnit->setId(null);
            $cloneDishUnit->setPlace($newPlace);
            $exception =
            if(!empty($dishUnitsCategoryArray[$dishUnit->getUnitCategory()->getId()])){

            }
            $cloneDishUnit->setUnitCategory();
            $em->persist($cloneDishUnit);
            $dishUnitsArray[$dishUnit->getId()] = $cloneDishUnit->getId();

            foreach ($dishUnit->getTranslations() as $translation) {
                $newDishUnitTranslation = clone $translation;
                $newDishUnitTranslation->setObject($dishUnit);
                $newDishUnitTranslation->setId(null);
                $em->persist($newDishUnitTranslation);
            }

        }
die;
        $comboDiscounts = $comboDiscountRepo->findBy(['place'=>$placeId]);

        foreach ($comboDiscounts as $comboDiscount){
            $comboDiscountClone = clone  $comboDiscount;
            $comboDiscountClone->setId(null);
            $comboDiscountClone->setPlace($newPlace);
            if($comboDiscount->getDishCategory()){
                $comboDiscountClone->setDishCategory($foodCategoriesArray[$comboDiscount->getId()]);
            }
            $comboDiscountClone->setDishUnit($dishUnitsArray[$comboDiscount->getId()]);
            $em->persist($comboDiscountClone);
        }

        //dishes

        $oldDishes = $dishRepo->findBy(['place' => $placeId]);

        if (!empty($oldDishes)) {
            foreach ($oldDishes as $key => $dish) {
                $newDish = clone $dish;
                $newDish->setId(null);
                $newDish->setPlace($newPlace);
                $newDish->setTranslations();
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
                    $cloneSize->setUnit($dishUnitsArray[$size->getId()]);
                    $em->persist($cloneSize);
                }


            }

        }


        $em->flush();

        return $newPlace->getId();
    }


}