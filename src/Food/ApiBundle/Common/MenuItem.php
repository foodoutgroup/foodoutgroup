<?php

namespace Food\ApiBundle\Common;

use Food\DishesBundle\Entity\Dish;
use Food\DishesBundle\Entity\DishUnit;
use Food\DishesBundle\Entity\Place;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MenuItem extends ContainerAware
{
    private $block = array(
        "item_id" => null,
        "restaurant_id" => null,
        "category_id" => null,
        "thumbnail_url" => '',
        "title" => '',
        "ingredients" => '',
        'show_discount' => false,
        "price_range" => array(
            "minimum" => 0,
            "maximum" => 0,
            "minimum_old" => 0,
            "maximum_old" => 0,
            "currency" => "LTL"
        ),
        "status" => 'available',
        "updated_at" => ""
    );
    public  $data;
    private $availableFields = array();

    /**
     * @param Place $place
     * @param ContainerInterface|null $container
     */
    public function __construct(Place $place = null, $container = null)
    {
        $this->data = $this->block;
        $this->availableFields = array_keys($this->block);
        if (!empty($place)) {
            $this->loadFromEntity($place);
        }
        $this->container = $container;
    }

    /**
     * @param string $param
     * @return mixed
     */
    public function get($param) {
        $this->checkParam($param);
        return $this->data[$param];
    }

    /**
     * @param string $param
     * @param mixed $data
     * @return MenuItem $this
     */
    public function set($param, $data)
    {
        $this->checkParam($param);
        $this->data[$param] = $data;
        return $this;
    }

    /**
     * @param string $param
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    private function checkParam($param)
    {
        if (!in_array($param, $this->availableFields)) {
            throw new Exception("Param: ".$param.", was not found in fields list :)");
        }
    }

    /**
     * @param Dish $dish
     * @param bool $loadOptions
     * @return array
     */
    public function loadFromEntity(Dish $dish, $loadOptions = false)
    {
        if (!$dish->getActive()) {
            return null;
        }

        $categories = array();
        foreach ($dish->getCategories() as $cat) {
            $categories[] = $cat->getId();
        }

        $ds = $this->container->get('food.dishes');
        $showDiscount = $dish->getShowDiscount();
        $discountText = "";
        if ($showDiscount) {
            $diff = ($ds->getLargestDishDiscountPrice($dish->getId()) / $ds->getLargestDishPrice($dish->getId())) * 100 - 100;
            $discountText = round($diff)."%";
        }
        $priceRange = array(
            'minimum' => (!$showDiscount ? $ds->getSmallestDishPrice($dish->getId()) * 100 : $ds->getSmallestDishDiscountPrice($dish->getId()) * 100),
            'maximum' => (!$showDiscount ? $ds->getLargestDishPrice($dish->getId()) * 100 : $ds->getLargestDishDiscountPrice($dish->getId()) * 100),
            'minimum_old' => ($showDiscount ? $ds->getSmallestDishPrice($dish->getId()) * 100 : 0),
            'maximum_old' => ($showDiscount ? $ds->getLargestDishPrice($dish->getId()) * 100 : 0),
            'discount_text' => $discountText,
            'currency' => 'LTL'
        );
        $this->set('item_id', $dish->getId())
            ->set('restaurant_id', $dish->getPlace()->getId())
            ->set('category_id', $categories)
            ->set('thumbnail_url',$dish->getWebPath())
            ->set('title', $dish->getName())
            ->set('ingredients', $dish->getDescription())
            ->set('show_discount', $showDiscount)
            ->set('price_range', $priceRange)
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
                // Jei dydis staiga dingo - tiesiog skipinkim si dydi
                $unit = $size->getUnit();
                if (!$unit instanceof DishUnit) {
                    continue;
                }

                $options['sizes']['items'][] = array(
                    'option_id' => $size->getId(),
                    'title' => $unit->getName(),
                    'price_modifier' => $size->getCurrentPrice() * 100,
                    'price_modifier_old' => ($size->getDish()->getShowDiscount() ?  $size->getPrice() * 100 : 0)

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
                    $optionsGroups[$name]['single'][] = $option;
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
                    $optionsGroups[$name]['multi'][] = $option;
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

            foreach ($optionsGroups as $key=>$optionsRow) {
                if (!empty($optionsRow['single'])) {
                    $optionList = array(
                        'title' => "",
                        'default' => null,
                        'type' => 'radio',
                        'items' => array()
                    );
                    $items = array();
                    foreach ($optionsRow['single'] as $opt) {
                        $items[] = array(
                            'option_id' => $opt->getId(),
                            'title' => $opt->getName(),
                            'price_modifier' => $opt->getPrice() * 100
                        );
                    }
                    $optionList['default'] = $items[0]['option_id'];
                    $optionList['items'] = $items;
                    $options[($key.'single')] = $optionList;
                }

                if (!empty($optionsRow['multi'])) {
                    $optionList = array(
                        'title' => "",
                        'type' => 'checkbox',
                        'items' => array()
                    );
                    $items = array();

                    foreach ($optionsRow['multi'] as $opt) {
                        $items[] = array(
                            'option_id' => $opt->getId(),
                            'title' => $opt->getName(),
                            'default' => false,
                            'price_modifier' => $opt->getPrice() * 100

                        );
                    }
                    $optionList['default'] = $items[0]['option_id'];
                    $optionList['items'] = $items;
                    $options[($key.'multi')] = $optionList;
                }
            }
            $options = array_values($options);
            if(!empty($options[1]) && $options[0]['type'] == "sizes") {
                $options[1]['title'] =  $this->container->get('translator')->trans('dish.select_options');
            }
            $this->data['options'] = $options;
        }
        return $this->data;
    }
}