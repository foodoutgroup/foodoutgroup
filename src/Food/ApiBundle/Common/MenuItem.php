<?php

namespace Food\ApiBundle\Common;

use Food\DishesBundle\Entity\Dish;
use Food\DishesBundle\Entity\Place;
use Symfony\Component\Config\Definition\Exception\Exception;
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
        "updated_at" => ""
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

    public function loadFromEntity(Dish $dish, $loadOptions = false)
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
            ->set('thumbnail_url',($dish->getWebPathThumb('type3')!="" ? 'http://www.foodout.lt/'.$dish->getWebPathThumb('type3') : ""))
            ->set('title', $dish->getName())
            ->set('ingredients', $dish->getDescription())
            ->set(
                'price_range',
                array(
                    'minimum' => $this->container->get('food.dishes')->getSmallestDishPrice($dish->getId()) * 100,
                    'maximum' => $this->container->get('food.dishes')->getLargestDishPrice($dish->getId()) * 100,
                    'currency' => 'LTL'
                )
            )
            ->set('updated_at', ($dish->getEditedAt() != null ? $dish->getEditedAt()->format('U'): $dish->getCreatedAt()->format('U')));

        if ($loadOptions) {
            $options = array(
                'sizes' => array(
                    'title' => $this->container->get('translator')->trans('dish.select_size'),
                    'type' => 'sizes',
                    'default' => null,
                    'items' => array()
                )
            );
            foreach($dish->getSizes() as $k=>$size) {
                $options['sizes']['items'][] = array(
                    'option_id' => $size->getId(),
                    'title' => $size->getUnit()->getName(),
                    'price_modifier' => $size->getPrice() * 100
                );
            }
            $options['sizes']['default'] = $options['sizes']['items'][0]['option_id'];


            $optionsGroups = array();

            foreach($dish->getOptions() as $option) {
                $name = $option->getGroupName();
                if (empty($name)) {
                    $name = '_def';
                }
                /*
                if (!isset($options[$name])) {
                    $options[$name] = array(
                        'title' => '',
                        'items' => array()
                    );
                }
                */
                if ($option->getSingleSelect()) {
                    $optionsGroups[$name]['single'] = $option;
                    /*
                    $options[$name]['items'][] = array(
                        'option_id' => $option->getId(),
                        'title' => $option->getName(),
                        'type' => 'radio',
                        'default' => false,
                        'price_modifier' => $option->getPrice() * 100
                    );
                    */
                } else {
                    $optionsGroups[$name]['multi'] = $option;
                    /*
                    $options[$name]['items'][] = array(
                        'option_id' => $option->getId(),
                        'title' => $option->getName(),
                        'type' => 'checkbox',
                        'default' => false,
                        'price_modifier' => $option->getPrice() * 100
                    );
                    */
                }
            }

            $this->data['options'] = array_values($options);
        }
        return $this->data;
    }
}