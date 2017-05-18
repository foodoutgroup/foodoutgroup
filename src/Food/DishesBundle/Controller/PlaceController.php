<?php

namespace Food\DishesBundle\Controller;

use Food\AppBundle\Entity\Slug;
use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlaceReviews;
use Food\UserBundle\Entity\User;
use Food\AppBundle\Utils\Misc;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PlaceController extends Controller
{
    public function indexAction($id, $slug, Request $request, $oldFriendIsHere = false)
    {
        $session = $this->get('session');
        $location = $this->get('food.googlegis')->getLocationFromSession();
        if ($session->get('isCallcenter')) {
            $session->set('isCallcenter', false);
        }

        // If no id - kill yourself
        if (empty($id)) {
            return $this->redirect($this->get('slug')->toHomepage(), 307);
        }

        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);

        // Place is incative, why show it?
        if (!$place || !$place instanceof Place || !$place->getActive()) {
            return $this->redirect($this->get('slug')->toHomepage(), 307);
        }


        $listType = 'thumbs';
        $cookies = $request->cookies;

        if ($cookies->has('restaurant_menu_layout')) {
            $listType = $cookies->get('restaurant_menu_layout');
        }

        $userLocationData = $this->get('food.googlegis')->getLocationFromSession();

        $breadcrumbData = [
            'city' => '',
            'city_url' => '',
            'kitchen' => '',
            'kitchen_url' => ''
        ];

        if(!isset($userLocationData['city_id'])) {
            $userLocationData['city_id'] = -1;
        }

        $cityObj = $this->getDoctrine()->getRepository('FoodAppBundle:City')->find($userLocationData['city_id']);
        if (!$cityObj) {
            $cityCollection = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->getCityCollectionByPlace($place);
            $cityObj = $cityCollection[0];
        }

        if(!$cityObj) {
            // todo tas funkcionalas kur hardcode for cili
            throw new NotFoundHttpException('City was not found');
        }

        $slug = $this->get('slug');

        $breadcrumbData['city'] = $cityObj->getTitle();
        $breadcrumbData['city_url'] = $slug->getUrl($cityObj->getId(), Slug::TYPE_CITY);

        $kitchens = $place->getKitchens();
        if (!empty($kitchens) && $kitchens->count() > 0) {
            $kitchen = $kitchens->first();
            $breadcrumbData['kitchen'] = $kitchen->getName();
            $kitchenSlug = $slug->getPath($kitchen->getId(), 'kitchen');
            $breadcrumbData['kitchen_url'] = $breadcrumbData['city_url'].'/'.$kitchenSlug;
        }


        $current_url = $request->getSchemeAndHttpHost() . $request->getRequestUri();

        $relatedPlaceCollection = [];

        // only for LT and only for cili
        // todo: MULTI-L someday move to database as conditions
        $relatedPlace = null;
        if ($this->container->getParameter('country') == 'LT') {
            if (in_array($place->getId(), [63, 85, 302, 333])) {
                $relatedPlaceCollection[] = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(142);
            } elseif ($place->getId() == 142) {
                $relatedPlaceCollection[] = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(63);
            }
        } elseif($this->container->getParameter('country') == 'LV'){
            if (in_array($place->getId(), [1, 35])) {
                $relatedPlaceCollection[] = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(36);
            } elseif ($place->getId() == 36) {
                $relatedPlaceCollection[] = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find(35);
            }
        }

        $placeService = $this->get('food.places');
        $takeAway = ($this->container->get('session')->get('delivery_type', false) == OrderService::$deliveryPickup);

        $variableCollection = [
            'place' => $place,
            'relatedPlaceCollection' => $relatedPlaceCollection,
            'wasHere' => $this->wasHere($place, $this->user()),
            'alreadyWrote' => $this->alreadyWrote($place, $this->user()),
            'placeCategories' => $placeService->getActiveCategories($place),
            'dishService' => $this->get('food.dishes'),
            'placePoints' => $placeService->getPublicPoints($place),
            'placePointsAll' => $placeService->getAllPoints($place),
            'listType' => $listType,
            'isTodayNoOneWantsToWork' => $this->get('food.order')->isTodayNoOneWantsToWork($place),
            'breadcrumbData' => $breadcrumbData,
            'current_url' => $current_url,
            'oldFriendIsHere' => $oldFriendIsHere,
            'takeAway' => $takeAway,
            'location' => $location
        ];

        if($this->get('food.app.utils.misc')->getParam('reviews_enabled', false)) {
            $variableCollection['placeReviewCollection'] = $this->get('doctrine')
                ->getRepository('FoodDishesBundle:PlaceReviews')
                ->getActiveReviewsByPlace($place);
        }


        return $this->render('FoodDishesBundle:Place:index.html.twig', $variableCollection);

    }

    public function filtersListAction()
    {
        return $this->render('FoodDishesBundle:Place:filter_list.html.twig');
    }

    public function placePointAction($point_id, Request $request)
    {
        $placeService = $this->get('food.places');

        $placePointData = array();
        $placePoint = $placeService->getPlacePointData($point_id);
        $place = $placePoint->getPlace();

        if ($placePoint->getActive() && $placePoint->getPublic()) {
            $placePointData = $placePoint->__toArray();
            $placePointData['allowInternetPayments'] = !$place->getDisabledOnlinePayment();
        }

        if ($placePoint->getPhone()) {
            $placePointData['phone'] = $placePoint->getPhone();
        }

        $response = new JsonResponse($placePointData);
        $response->setCharset('UTF-8');
        $response->prepare($request);

        return $response;
    }

    public function reviewAction($id)
    {
        if (empty($id)) {
            return $this->redirect($this->get('slug')->toHomepage(), 307);
        }
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        $review = $this->defaultReview($place, $this->user());

        return $this->render(
            'FoodDishesBundle:Place:review.html.twig',
            [
                'place' => $place,
                'form' => $this->reviewForm($review)->createView()
            ]
        );
    }

    public function reviewCreateAction($id, Request $request)
    {

        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        if ($place) {
            return $this->redirect($this->get('slug')->toHomepage(), 307);
        }

        $review = $this->defaultReview($place, $this->user());
        $form = $this->reviewForm($review);
        $placeService = $this->get('food.places');

        $errors = array();

        // apply data from submitted data to symfony form
        $form->handleRequest($request);
        $score = (int)$request->request->get('score');
        $formVar = $request->request->get('form');
        $reviewText = (string)$formVar['review'];

        if ($form->isValid() && $score >= 1 && $score <= 5 && !empty($reviewText)) {
            $em = $this->getDoctrine()->getManager();

            // field 'rate' is neither mapped nor in symfony form, so update manually
            $review->setRate($request->request->get('score'));

            // commit changed to review
            $em->persist($review);
            $em->flush();

            $averageRating = $placeService->calculateAverageRating($place);

            $place->setAverageRating($averageRating);
            $place->setReviewCount(count($place->getReviews()));
            $placeService->savePlace($place);

            return new JsonResponse(['success' => true]);
        } else {
            $translator = $this->get('translator');

            if (empty($reviewText)) {
                $errors[] = $translator->trans('places.reviews.empty_review');
            }
            if (empty($score) || ($score < 1 || $score > 5)) {
                $errors[] = $translator->trans('places.reviews.empty_rating');
            }
        }

        return new JsonResponse(['success' => false, 'errors' => $errors]);
    }

    private function getUserOrderCount(Place $place = null, User $user = null)
    {
        $count = (int)$this
            ->getDoctrine()
            ->getManager()
            ->createQueryBuilder()
            ->select('COUNT(o)')
            ->from('FoodOrderBundle:Order', 'o')
            ->where('o.place = :place')
            ->andWhere('o.user = :user')
            ->andWhere('o.order_status IN (:statuses)')
            ->setParameters(
                [
                    'place' => $place,
                    'user' => $user,
                    'statuses' => array(
                        OrderService::$status_completed,
                    )
                ]
            )
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }

    private function wasHere(Place $place = null, User $user = null)
    {
        return $this->getUserOrderCount($place, $user);
    }

    private function alreadyWrote(Place $place = null, User $user = null)
    {
        $reviews = (int)$this
            ->getDoctrine()
            ->getManager()
            ->createQueryBuilder()
            ->select('COUNT(pr)')
            ->from('FoodDishesBundle:PlaceReviews', 'pr')
            ->where('pr.place = :place')
            ->andWhere('pr.createdBy = :user')
            ->setParameters(['place' => $place, 'user' => $user])
            ->getQuery()
            ->getSingleScalarResult();

        $orders = $this->getUserOrderCount($place, $user);

        if ($reviews >= $orders) {
            return true;
        }
        return false;
    }

    private function user()
    {
        $sc = $this->get('security.context');

        if (!$sc->isGranted('ROLE_USER')) {
            return null;
        }

        return $sc->getToken()->getUser();
    }

    private function reviewForm(PlaceReviews $review)
    {
        return $this
            ->createFormBuilder($review, ['csrf_protection' => false])
            ->add('review', 'textarea', ['required' => true, 'label' => 'general.review'])
            ->getForm();
    }

    private function defaultReview(Place $place = null, User $user = null)
    {
        $review = new PlaceReviews();
        $review
            ->setPlace($place)
            ->setCreatedBy($user)
            ->setCreatedAt(new \DateTime());

        return $review;
    }

    public function getPlaceUrlByCityAction($placeId, Request $request)
    {
        $placeService = $this->get('food.places');

        $found_data = ['status' => 'fail', 'city' => null, 'url' => null];

        $cityObj = $this->getDoctrine()->getRepository('FoodAppBundle:City')->find($request->get('city'));

        if ($cityObj && !empty($placeId)) {
            $url = $placeService->getPlaceUrlByCity($placeId, $cityObj->getTitle());
            if (!empty($url)) {
                $found_data = [
                    'status' => 'success',
                    'city' => $cityObj->getTitle(),
                    'url' =>    $this->get('slug')->getUrl($placeId, Slug::TYPE_PLACE),
                ];
                $this->get('food.googlegis')->setCity($cityObj);
            }
        }

        $response = new JsonResponse($found_data);
        $response->setCharset('UTF-8');
        $response->prepare($request);

        return $response;
    }

    public function getCitiesByPlaceAction($placeId, Request $request)
    {
        $placeService = $this->get('food.places');
        $found_data = ['status' => 'fail', 'cities' => null];

        if (!empty($placeId)) {
            $cities = $placeService->getCitiesByPlace($placeId);
            if (!empty($cities)) {
                $found_data = [
                    'status' => 'success',
                    'cities' => $cities
                ];
            }
        }

        $response = new JsonResponse($found_data);
        $response->setCharset('UTF-8');
        $response->prepare($request);

        return $response;
    }
}
