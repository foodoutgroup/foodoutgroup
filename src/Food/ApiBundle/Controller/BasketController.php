<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BasketController extends Controller
{
    public function createBasketAction(Request $request)
    {
        return new JsonResponse($this->get('food_api.basket')->createBasketFromRequest($request));
    }

    public function updateBasketAction($id, Request $request)
    {
        return new JsonResponse($this->get('food_api.basket')->updateBasketFromRequest($id, $request));
    }

    public function getBasketAction($id)
    {
        $basket = $this->get('food_api.basket')->getBasket($id);
        return new JsonResponse($basket);
    }

    public function deleteBasketAction($id)
    {
        $this->get('food_api.basket')->deleteBasket($id);
        return new Response('', 204);
    }

    public function updateBasketItemAction($id, $basket_item_id, Request $request)
    {
        $this->get('food_api.basket')->updateBasketItem($id, $basket_item_id, $request);
        $basket = $this->get('food_api.basket')->getBasket($id);
        return new JsonResponse($basket);
    }

    public function deleteBasketItemAction($id, $basket_item_id)
    {
        $this->get('food_api.basket')->deleteBasketItem($id, $basket_item_id, $request);
        $basket = $this->get('food_api.basket')->getBasket($id);
        return new JsonResponse('', 204);
    }

     /**
     * JSON beautifier
     *
     * @param string    The original JSON string
     * @param   string  Return string
     * @param string    Tab string
     * @return string
     */
    public function pretty_json($json, $ret= "\n", $ind="    ") {

        $beauty_json = '';
        $quote_state = FALSE;
        $level = 0;

        $json_length = strlen($json);

        for ($i = 0; $i < $json_length; $i++)
        {

            $pre = '';
            $suf = '';

            switch ($json[$i])
            {
                case '"':
                    $quote_state = !$quote_state;
                    break;

                case '[':
                    $level++;
                    break;

                case ']':
                    $level--;
                    $pre = $ret;
                    $pre .= str_repeat($ind, $level);
                    break;

                case '{':

                    if ($i - 1 >= 0 && $json[$i - 1] != ',')
                    {
                        $pre = $ret;
                        $pre .= str_repeat($ind, $level);
                    }

                    $level++;
                    $suf = $ret;
                    $suf .= str_repeat($ind, $level);
                    break;

                case ':':
                    $suf = ' ';
                    break;

                case ',':

                    if (!$quote_state)
                    {
                        $suf = $ret;
                        $suf .= str_repeat($ind, $level);
                    }
                    break;

                case '}':
                    $level--;

                case ']':
                    $pre = $ret;
                    $pre .= str_repeat($ind, $level);
                    break;

            }

            $beauty_json .= $pre.$json[$i].$suf;

        }

        return $beauty_json;

    }
}