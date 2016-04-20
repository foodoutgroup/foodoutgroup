<?php

namespace Food\DishesBundle\Controller;

use Food\DishesBundle\Entity\Dish;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class DishController extends Controller
{
    /**
     * Disho langas kad prideti i cart
     *
     * @param $dish
     * @return Response
     */
    public function getDishAction($dish)
    {
        try {
            $dishEnt = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
            if ($dishEnt instanceof Dish) {
                $sizeCount = sizeof($dishEnt->getSizes());
            } else {
                return $this->render(
                    'FoodDishesBundle:Dish:no_dish.html.twig'
                );
            }
        } catch (\Exception $e) {
            return $this->render(
                'FoodDishesBundle:Dish:no_dish.html.twig'
            );
        }
        $randomized = $this->get('session')->get('randomizer', false);
        if (!$randomized) {
            $randomized = rand(0, 8);
            $this->get('session')->set('randomizer', $randomized);
        }
        if (isset($_GET['randomizer'])) {
            $randomized = (int)$_GET['randomizer'];
            $this->get('session')->set('randomizer', $randomized);
            die("DIE");
        }
        $countDown = $this->get('session')->get('countdown', false);
        if (!$countDown) {
            $countDown = date("U") + 600;
            $this->get('session')->set('countdown', $countDown);
        } else {
            if ($countDown - date("U") <= 30) {
                $countDown = date("U") + 600;
                $this->get('session')->set('countdown', $countDown);
            }
        }
        $selSize = 1;
        if ($sizeCount == 3) {
            $selSize = 2;
        } elseif ($sizeCount == 4) {
            $selSize = 3;
        }
        $miscService = $this->get('food.app.utils.misc');
        $shifter = $miscService->parseTimeToMinutes($dishEnt->getPlace()->getDeliveryTime());
        $extraTestStuff = array(
            '0' => array(),
            '1' => array('message' => 'Šį patiekalą pristatėme jau 100+ klientų! Užsisakyk ir tu!', 'counter' => false),
            '2' => array('message' => 'Šį patiekalą pristatėme jau 100+ klientų! Užsisakyk ir tu!', 'counter' => false),
            '3' => array('message' => 'Patiekalas jau rezervuotas! Nepraleisk progos ir užsisakyk!', 'counter' => false),
            '4' => array('message' => 'Patiekalas jau rezervuotas! Nepraleisk progos ir užsisakyk!', 'counter' => false),
            '5' => array('message' => 'Greičiau užsakysi – greičiau gausi!', 'counter' => false),
            '6' => array('message' => 'Greičiau užsakysi – greičiau gausi!', 'counter' => false),

        );
        return $this->render(
            'FoodDishesBundle:Dish:dish.html.twig',
            array(
                'randomizer' => $randomized,
                'countdown' => (int)$countDown,
                'currentcount' => (int)date("U"),
                'extraMessages' => $extraTestStuff,
                'dish' => $dishEnt,
                'selectedSize' => $selSize,
                'cart' => null,
                'place' => $dishEnt->getPlace()
            )
        );
    }

    /**
     * Disho editas carte.
     *
     * @param $dish
     * @param $cartId
     * @return Response
     *
     * @todo - tikrinti issue :) Yra sukurta. Pajungti ir sutvarkyti
     */
    public function editDishInCartAction($dish, $cartId)
    {
        $dishEnt = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
        $cartEnt = $this->get('food.cart')->getCartDish(intval($dish), intval($cartId));
        return $this->render(
            'FoodDishesBundle:Dish:dish.html.twig',
            array(
                'dish' => $dishEnt,
                'cart' => $cartEnt
            )
        );
    }

    /**
     * Disho editas carte.
     *
     * @param int $dish
     * @param int $cartId
     * @param int $inCart
     * @return Response
     */
    public function removeDishInCartAction($dish, $cartId, $inCart)
    {
        $dishEnt = $this->getDoctrine()->getRepository('FoodDishesBundle:Dish')->find((int)$dish);
        $cartEnt = $this->get('food.cart')->getCartDish(intval($dish), intval($cartId));
        return $this->render(
            'FoodDishesBundle:Dish:dish_remove.html.twig',
            array(
                'dish' => $dishEnt,
                'cart' => $cartEnt,
                'inCart' => $inCart,
            )
        );
    }
}