<?php

namespace Food\DishesBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlaceReviews;
use Food\UserBundle\Entity\User;

class PlaceController extends Controller
{
    public function indexAction($id, $slug, $categoryId, Request $request)
    {

        $session = $this->get('session');
        $isCallcenter = $session->get('isCallcenter');
        if ($isCallcenter) {
            $session->set('isCallcenter', false);
        }

        // If no id - kill yourself
        if (empty($id)) {
            return $this->redirect(
                $this->generateUrl('food_homepage'),
                307
            );
        }

        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);

        // Place is incative, why show it?
        if (!$place || !$place instanceof Place || $place->getActive() == false) {
            return $this->redirect(
                $this->generateUrl('food_homepage'),
                307 // Temporary redirect status
            );
        }

        $categoryList = $this->get('food.places')->getActiveCategories($place);
        $placePoints = $this->get('food.places')->getPublicPoints($place);
        $placePointsAll = $this->get('food.places')->getAllPoints($place);
//        $categoryRepo = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory');
        $dishService = $this->get('food.dishes');

        $listType = 'thumbs';
        $cookies = $request->cookies;

        if ($cookies->has('restaurant_menu_layout')) {
            $listType = $cookies->get('restaurant_menu_layout');
        }
/**
        if (!empty($categoryId)) {
            $activeCategory = $categoryRepo->find($categoryId);
        } else {
            $activeCategory = $categoryList[0];
        }
*/
        $wasHere = $this->wasHere($place, $this->user());
        $alreadyWrote = $this->alreadyWrote($place, $this->user());
        $isTodayNoOneWantsToWork = $this->get('food.order')->isTodayNoOneWantsToWork($place);

        $breadcrumbData = array(
            'city' => '',
            'city_url' => '',
            'kitchen' => '',
            'kitchen_url' => ''
        );

        $locationData =  $this->get('food.googlegis')->getLocationFromSession();

        if (isset($locationData['city'])) {
            $cityInfo = $this->get('food.city_service')->getCityInfo($locationData['city']);
            if (!empty($cityInfo)) {
                $breadcrumbData = array_merge($breadcrumbData, $cityInfo);
            }

            $kitchens = $place->getKitchens();
            if(!empty($kitchens)) {
                $kitchen = $kitchens->first();
                $breadcrumbData['kitchen'] = $kitchen->getName();
                $kitchenSlug = $this->get('food.dishes.utils.slug')->getSlugByItem($kitchen->getId(), 'kitchen');
                $breadcrumbData['kitchen_url'] = $this->generateUrl('food_city_'.$cityInfo['city_slug_lower'], array(), true).'/'.$kitchenSlug;
            }
        }

        return $this->render(
            'FoodDishesBundle:Place:index.html.twig',
            array(
                'place' => $place,
                'wasHere' => $wasHere,
                'alreadyWrote' => $alreadyWrote,
                'placeCategories' => $categoryList,
                'dishService' => $dishService,
                // 'selectedCategory' => $activeCategory,
                'placePoints' => $placePoints,
                'placePointsAll' => $placePointsAll,
                'listType' => $listType,
                'isTodayNoOneWantsToWork' => $isTodayNoOneWantsToWork,
                'breadcrumbData' => $breadcrumbData,
            )
        );
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

        $response = new JsonResponse($placePointData);
        $response->setCharset('UTF-8');
        $response->prepare($request);

        return $response;
    }

    public function reviewAction($id)
    {
        if (empty($id)) {
            return $this->redirect(
                $this->generateUrl('food_homepage'),
                307
            );
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
        if (empty($id)) {
            return $this->redirect(
                $this->generateUrl('food_homepage'),
                307
            );
        }
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        $review = $this->defaultReview($place, $this->user());
        $form = $this->reviewForm($review);
        $placeService = $this->get('food.places');

        $errors = array();

        // apply data from submitted data to symfony form
        $form->handleRequest($request);
        $score = (int) $request->request->get('score');
        $formVar = $request->request->get('form');
        $reviewText = (string) $formVar['review'];

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
        $count = (int) $this
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
            ->getSingleScalarResult()
        ;

        return $count;
    }

    private function wasHere(Place $place = null, User $user = null)
    {
        $count = $this->getUserOrderCount($place, $user);

        return $count ? true : false;
    }

    private function alreadyWrote(Place $place = null, User $user = null)
    {
        $reviews = (int) $this
            ->getDoctrine()
            ->getManager()
            ->createQueryBuilder()
            ->select('COUNT(pr)')
            ->from('FoodDishesBundle:PlaceReviews', 'pr')
            ->where('pr.place = :place')
            ->andWhere('pr.createdBy = :user')
            ->setParameters(['place' => $place, 'user' => $user])
            ->getQuery()
            ->getSingleScalarResult()
        ;

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
            ->getForm()
        ;
    }

    private function defaultReview(Place $place = null, User $user = null)
    {
        $review = new PlaceReviews();
        $review
            ->setPlace($place)
            ->setCreatedBy($user)
            ->setCreatedAt(new \DateTime())
        ;

        return $review;
    }
}
