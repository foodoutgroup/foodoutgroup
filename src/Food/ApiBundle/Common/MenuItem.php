<?php

namespace Food\ApiBundle\Common;

use Food\DishesBundle\Entity\Dish;
use Food\DishesBundle\Entity\Place;
use Symfony\Component\DependencyInjection\ContainerAware;

class MenuItem extends ContainerAware
{
    private $block = array(
        "item_id" => null,
        "restaurant_id" => null,
        "category_id" => null,
        "thumbnail_url" => '',
        "title" => '',
        "ingredients" => '',
        "price_range" => array(
            "minimum" => 0,
            "maximum" => 0,
            "currency" => "LTL"
        ),
        "status" => 'available',
        "updated_at" => null
    );
    public  $data;
    private $availableFields = array();


    public function __construct(Place $place = null, $container = null)
    {
        $this->data = $this->block;
        $this->availableFields = array_keys($this->block);
        if (!empty($place)) {
            $this->loadFromEntity($place);
        }
        $this->container = $container;
    }

    public function get($param) {
        $this->checkParam($param);
        return $this->data[$param];
    }

    /**
     * @param $param
     * @param $data
     * @return \MenuItem $this
     */
    public function set($param, $data)
    {
        $this->checkParam($param);
        $this->data[$param] = $data;
        return $this;
    }

    /**
     * @param $param
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    private function checkParam($param)
    {
        if (!in_array($param, $this->availableFields)) {
            throw new Exception("Param: ".$param.", was not found in fields list :)");
        }
    }

    public function loadFromEntity(Dish $dish)
    {
        if (!$dish->getActive()) {
            return null;
        }

        $categories = array();
        foreach ($dish->getCategories() as $cat) {
            $categories[] = $cat->getId();
        }

        $this->set('item_id', $dish->getId())
            ->set('restaurant_id', $dish->getPlace()->getId())
            ->set('category_id', $categories)
            ->set('thumbnail_url','http://www.foodout.lt/'.$dish->getWebPathThumb('type3'))
            ->set('title', $dish->getName())
            ->set('ingredients', $dish->getDescription())
            ->set(
                'price_range',
                array(
                    'minimum' => $this->container->get('food.dishes')->getSmallestDishPrice($dish->getId()),
                    'maximum' => $this->container->get('food.dishes')->getLargestDishPrice($dish->getId()),
                    'currency' => 'LTL'
                )
            )
            ->set('updated_at', ($dish->getEditedAt() != null ? $dish->getEditedAt()->format('U'): $dish->getCreatedAt()->format('U')));
        return $this->data;
    }
}