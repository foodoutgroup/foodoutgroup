<?php

namespace Api\V2Bundle\Controller;

use Api\BaseBundle\Exceptions\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RestaurantController extends Controller
{

    public function getMenuAction($placeHash, Request $request){

        try {
            $ps = $this->get('api.v2.place');

            $return = ['success' => false];
            $place = $ps->getPlaceByHash($placeHash);

            $collection = [];
            foreach ($place->getDishes() as $dish) {
                $dishCollection = [];
                $dishCollection['name'] = $dish->getName();
                $dishCollection['description'] = $dish->getDescription();
                $dishCollection['image'] = 'http://'.$this->container->getParameter('cloudfront_url').'/'.$dish->getWebPathThumb('type3');

                $sizeCollection = [];
                foreach ($dish->getSizes() as $size) {
                    $sizeCollection[] = [
                        'code' => $size->getCode(),
                        'price' => $size->getCurrentPrice(),
                        'unit' => $size->getUnit()->getName(),
                    ];
                }
                $dishCollection['size'] = $sizeCollection;

                $additionalCollection = [];
                foreach ($dish->getOptions() as $option) {
                    $additionalCollection[] = [
                        'code' => $option->getCode(),
                        'name' => $option->getName(),
                    ];
                }
                $dishCollection['additional'] = $additionalCollection;

                $categoryCollection = [];
                foreach ($dish->getCategories() as $category) {
                    $categoryCollection[] = $category->getId();
                }
                $dishCollection['category'] = $categoryCollection;

                $collection[] = $dishCollection;
            }

            $return['collection'] = $collection;
            $return['success'] = true;
        } catch (ApiException $e) {
            $return['message'] = $e->getMessage();
        }

        return  new JsonResponse($return);

    }

    public function getMenuCategoryAction($placeHash, Request $request)
    {
        $ps = $this->get('api.v2.place');
        $return = ['success' => false];
        try {

            $place = $ps->getPlaceByHash($placeHash);
            $categoryCollection = $ps->getActiveCategories($place);
            $collection = [];
            foreach ($categoryCollection as $category) {
                $collection[] = [
                    'id' => $category->getId(),
                    'title' => $category->getName()
                ];
            }
            $return['collection'] = $collection;
            $return['success'] = true;
        } catch (ApiException $e) {
            $return['message'] = $e->getMessage();
        }
//        $xml_data = new \SimpleXMLElement('<?xml version="1.0"?\>//<data></data>');
//        $this->array_to_xml($return, $xml_data);
//        header("Content-type: text/xml");
//        echo $xml_data->asXML();
//        die;
        return  new JsonResponse($return);
    }

    public function loyaltyAction($placeHash, Request $request){

        $ps = $this->get('api.v2.place');
        $return = ['success' => false];
        try {

            $place = $ps->getPlaceByHash($placeHash);

            $response = $this->container->get('api.v2.loyalty_card')->validate($place, $request->get("code"), $request->get("type"));
            $return['success'] = true;
            $return['message'] = $response['message'];
            $return['discount'] = $response['discount'];
            $return['title'] = $response['title'];

        } catch (ApiException $e) {
            $return['message'] = $e->getMessage();
        }

        return new JsonResponse($return);
    }


    function array_to_xml( $data, &$xml_data ) {
        foreach( $data as $key => $value ) {
            if( is_numeric($key) ){
                $key = 'item'; //dealing with <0/>..<n/> issues
            }
            if( is_array($value) ) {
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }

}
