<?php

namespace Api\V2Bundle\Controller;

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
                $foundCity = [];
                $cityCollection = [];
                $placeGroupIDs = [63, 143, 85, 105,142,333,302,160];

                $repository = $this->getDoctrine()
                    ->getRepository('FoodDishesBundle:PlacePoint');

                $qb = $repository->createQueryBuilder('pp')
                    ->join('FoodDishesBundle:Place', 'p', Join::WITH,'p.id = pp.place')
                    ->where('pp.active = 1')
                    ->andWhere('p.deletedAt IS NULL')
                    ->andWhere('p.id IN (:ids)')
                    ->andWhere('p.apiHash IS NOT NULL');

                $query = $qb->getQuery()->setParameters(['ids' => $placeGroupIDs]);

                $result = $query->execute();

                foreach ($result as $placePoint) {

                    if(in_array($placePoint->getCity(), $foundCity)) {
                        continue;
                    }

                    $foundCity[] = $placePoint->getCity();
                    $place = $placePoint->getPlace();
                    $cityCollection[] = [
                        'city' => $placePoint->getCity(),
                        'hash' => $place->getApiHash(),

                    ];
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
