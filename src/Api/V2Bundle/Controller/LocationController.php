<?php

namespace Api\V2Bundle\Controller;

use Doctrine\ORM\Query\Expr\Join;
use Food\DishesBundle\Entity\PlacePoint;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LocationController extends Controller
{
    /**
     * karolis nesake kaip daryt :D padariau kaip greiciau :D
     * @param $providerHash
     * @return JsonResponse
     */
    public function getCityAction($providerHash, Request $request)
    {
        $response = ['success' => false];
        switch ($providerHash) {
            case "3MezrzQPc8gCdXDtpsQETj1qD57iISrd53xMR01UfpIYZU0hxgXioN2QY3GBZo8P": // todo: kazkaip pakeisti cia special for cili
                $foundCity = [];
                $cityCollection = [];
                $placeGroupIDs = [63, 143, 85, 105,142,333,302,160];
                $placeGroupIDsTakeAway = [];

                if($request->get('version') == 2) {
                    $placeGroupIDsTakeAway = [142];
                }

                $repository = $this->getDoctrine()
                    ->getRepository('FoodDishesBundle:PlacePoint');

                $qb = $repository->createQueryBuilder('pp')
                    ->join('FoodDishesBundle:Place', 'p', Join::WITH,'p.id = pp.place')
                    ->where('pp.active = 1')
                    ->andWhere('p.deletedAt IS NULL')
                    ->andWhere('p.id IN (:ids)')
                    ->andWhere('p.apiHash IS NOT NULL');

                $query = $qb->getQuery()->setParameters(['ids' => array_merge($placeGroupIDs, $placeGroupIDsTakeAway)]);

                $result = $query->execute();
                /**
                 * @var $placePoint PlacePoint
                 */
                $version = intval($request->get('version', 0));

                foreach ($result as $placePoint) {

                    $place = $placePoint->getPlace();
                    $cityObj = $placePoint->getCityId();
                    if(!$cityObj) {
                        continue;
                    }

                    if($version >= 2) {

                        if(!isset($cityCollection[$cityObj->getId()])) {
                            $cityCollection[$cityObj->getId()] = [
                                'title' => $cityObj->getTitle(),
                                'takeaway' => false,
                                'hash' => false,
                            ];
                        }
                        $key = in_array($place->getId(), $placeGroupIDsTakeAway) ? 'takeaway' : 'hash';
                        $cityCollection[$cityObj->getId()][$key] = $place->getApiHash();

                    } else {

                        $cityObj = $placePoint->getCityId();
                        if (!$cityObj || in_array($cityObj->getTitle(), $foundCity)) {
                            continue;
                        }

                        $foundCity[] = $cityObj->getTitle();
                        $place = $placePoint->getPlace();
                        $cityCollection[] = [
                            'city' => $cityObj->getTitle(),
                            'hash' => $place->getApiHash(),
                        ];
                    }
                }

                if($version >= 2) {
                    $realData = [];

                    foreach ($cityCollection as $value) {
                        $realData[] = $value;
                    }
                    $cityCollection = $realData;

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
