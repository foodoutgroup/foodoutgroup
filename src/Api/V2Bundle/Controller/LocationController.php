<?php

namespace Api\V2Bundle\Controller;

use Api\BaseBundle\Exceptions\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class LocationController extends Controller
{
    /**
     * karolis nesake kaip daryt :D padariau kaip greiciau :D
     * @param $providerHash
     * @return JsonResponse
     */
    public function getCityAction($providerHash)
    {
        $response = ['success' => false];

        switch ($providerHash) {
            case "3MezrzQPc8gCdXDtpsQETj1qD57iISrd53xMR01UfpIYZU0hxgXioN2QY3GBZo8P": // todo: kazkaip pakeisti cia special for cili

                $cityCollection = [];
                $placeGroupIDs = [63,85, 105,142,333,302,160];
                $cityConfigCollection = $this->container->getParameter('available_cities');

                foreach ($cityConfigCollection as $city) {

                    $repository = $this->getDoctrine()
                        ->getRepository('FoodDishesBundle:PlacePoint');

                    $qb = $repository->createQueryBuilder('pp')
                        ->innerJoin('FoodDishesBundle:Place', 'p', 'WITH','p.id = pp.place')
                        ->where('pp.city = :city')
                        ->andWhere('pp.active = 1')
                        ->andWhere('p.deletedAt IS NULL')
                        ->andWhere('p.id IN (:ids)')
                        ->andWhere('p.apiHash IS NOT NULL');

                    $query = $qb->getQuery()->setParameters(['city' => $city, 'ids' => implode(",",$placeGroupIDs)]);

                    $result = $query->execute();
                    $place = (count($result) >= 1 ? $result[0]->getPlace() : null);

                    $dataArray = [];
                    foreach ($result as $placePoint) {

                        $place = $placePoint->getPlace();

                            $dataArray['city'] = $city;
                            $dataArray['hash'] = $place->getApiHash();
                            $dataArray['points'][] = [
                                'id' => $placePoint->getId(),
                                'address' => $placePoint->getAddress(),
                                'work_hour' => [
                                    $placePoint->getWd1(),
                                    $placePoint->getWd2(),
                                    $placePoint->getWd3(),
                                    $placePoint->getWd4(),
                                    $placePoint->getWd5(),
                                    $placePoint->getWd6(),
                                    $placePoint->getWd7(),
                                ]
                            ];
                    }

                    $cityCollection[] = $dataArray;
//                    var_dump($dataArray);
//                    die;
//
//                    $dataArray = [];
//
//                    if($place != null && $place->getActive()) {
//                        $cityCollection[] = [
//                            'city' => $city,
//                            'hash' => $place->getApiHash(),
//                        ];
//                    }
                }

                $response['success'] = true;
                $response['collection'] = $cityCollection;

                break;
            default:
                $response['message'] = 'Provider not found';
                break;
        }
        return new JsonResponse($response);
    }
}
