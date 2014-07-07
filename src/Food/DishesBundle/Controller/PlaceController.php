<?php

namespace Food\DishesBundle\Controller;

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
        $place = $this->getDoctrine()->getRepository('FoodDishesBundle:Place')->find($id);
        $categoryList = $this->get('food.places')->getActiveCategories($place);
        $placePoints = $this->get('food.places')->getPublicPoints($place);
        $placePointsAll = $this->get('food.places')->getAllPoints($place);
        $categoryRepo = $this->getDoctrine()->getRepository('FoodDishesBundle:FoodCategory');

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
        return $this->render(
            'FoodDishesBundle:Place:index.html.twig',
            array(
                'place' => $place,
                'wasHere' => $wasHere,
                'alreadyWrote' => $alreadyWrote,
                'placeCategories' => $categoryList,
                // 'selectedCategory' => $activeCategory,
                'placePoints' => $placePoints,
                'placePointsAll' => $placePointsAll,
                'listType' => $listType,
            )
        );
    }

    public function filtersListAction()
    {
        return $this->render('FoodDishesBundle:Place:filter_list.html.twig');
    }

    public function placePointAction($point_id)
    {
        $placeService = $this->get('food.places');

        $placePointData = array();
        $placePoint = $placeService->getPlacePointData($point_id);
        if ($placePoint->getActive() && $placePoint->getPublic()) {
            $placePointData = $placePoint->__toArray();
        }

        $response = new JsonResponse($placePointData);
        $response->setCharset('UTF-8');

        $response->prepare($this->getRequest());
        return $response;
    }

    public function reviewAction($id)
    {
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

    private function wasHere(Place $place = null, User $user = null)
    {
        $count = (int) $this
            ->getDoctrine()
            ->getManager()
            ->createQueryBuilder()
            ->select('COUNT(o)')
            ->from('FoodOrderBundle:Order', 'o')
            ->where('o.place = :place')
            ->andWhere('o.user = :user')
            ->setParameters(['place' => $place, 'user' => $user])
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count ? true : false;
    }

    private function alreadyWrote(Place $place = null, User $user = null)
    {
        $review = $this
            ->getDoctrine()
            ->getManager()
            ->createQueryBuilder()
            ->select('pr')
            ->from('FoodDishesBundle:PlaceReviews', 'pr')
            ->where('pr.place = :place')
            ->andWhere('pr.createdBy = :user')
            ->setParameters(['place' => $place, 'user' => $user])
            ->getQuery()
            ->getResult()
        ;

        return $review ? true : false;
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
