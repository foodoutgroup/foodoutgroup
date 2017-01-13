<?php

namespace Api\V2Bundle\Service;

use Api\BaseBundle\Exceptions\ApiException;
use Food\DishesBundle\Entity\Place;
use Symfony\Component\DependencyInjection\ContainerAware;

class LoyaltyCardService extends ContainerAware
{
    public function validate(Place $place, $code, $type = null){
        $return = ['success' => false];
        $ch = curl_init();
        if(empty($place->getCouponURL())){
            throw new ApiException('Verification address has not been established');
        } else {

            curl_setopt($ch, CURLOPT_URL, $place->getCouponURL());
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query(['code' => $code, 'type' => $type]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);

            $jr = json_decode($server_output, true);

            if(is_array($jr) && isset($jr['success']) && (bool) $jr['success']) {

                if(!isset($jr['title']) || !isset($jr['message']) || !isset($jr['discount'])) {
                    throw new ApiException('Missing returning parameters. Required: title, message, discount');
                } else {

                    $discount = (int) $jr['discount'];
                    if($discount > 100) {
                        $discount = 100;
                    }elseif($discount < 0) {
                        $discount = 0;
                    }

                    $return['success'] = true;
                    $return['discount'] = $discount;
                    $return['title'] = $jr['title'];
                    $return['message'] = $jr['message'];

                }
            } else {
                throw new ApiException('Bad response from client server: ' . curl_error($ch));
            }
        }
        curl_close ($ch);

        return $return;
    }

}

