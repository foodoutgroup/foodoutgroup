<?php

namespace Food\AppBundle\Controller;

use Food\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pirminis\Gateway\Swedbank\FullHps\Request as FullHpsRequest;
use Pirminis\Gateway\Swedbank\FullHps\Response as FullHpsResponse;
use Pirminis\Gateway\Swedbank\FullHps\Request\Parameters;
use Pirminis\Gateway\Swedbank\Banklink\Sender;
use Pirminis\Gateway\Swedbank\FullHps\TransactionQuery\Request as FullHpsTransRequest;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {

        $miscUtils = $this->get('food.app.utils.misc');
        // Check if user is not banned
        $ip = $request->getClientIp();
        // Dude is banned - hit him
        if ($miscUtils->isIpBanned($ip)) {
            return $this->redirect($this->generateUrl('banned'), 302);
        }

        $formDefaults = array(
            'city' => '',
            'address' => '',
            'house' => '',
            'flat' => '',
        );

        $user = $this->getUser();

        if ($user instanceof User) {
            $defaultUserAddress = $user->getCurrentDefaultAddress();
            if (!empty($defaultUserAddress)) {
                $addressData = $miscUtils->parseAddress($defaultUserAddress->getAddress());

                $formDefaults = array(
                    'city' => $defaultUserAddress->getCity(),
                    'address' => $addressData['street'],
                    'house' => $addressData['house'],
                    'flat' => $addressData['flat']
                );
            }
        }

        // Lets check session for last used address and use it
        $sessionLocation = $this->get('food.googlegis')->getLocationFromSession();
        if (!empty($sessionLocation)
            && !empty($sessionLocation['city']) && !empty($sessionLocation['address_orig'])) {
            $addressData = $miscUtils->parseAddress($sessionLocation['address_orig']);

            $formDefaults = array(
                'city' => $sessionLocation['city'],
                'address' => $addressData['street'],
                'house' => $addressData['house'],
                'flat' => $addressData['flat']
            );
        }

        $cityCollection = $this->get("food.city_service")->getActiveCity();

        return $this->render(
            'FoodAppBundle:Default:index.html.twig', [
                'formDefaults' => $formDefaults,
                'cityCollection' => $cityCollection,
            ]
        );
    }

    public function footerAction()
    {
        $topRatedPlaces = $this->get('food.places')->getTopRatedPlaces(12);
        $staticPages = $this->get('food.static')->getActivePages(10);

        $cityService = $this->get('food.city_service');

        return $this->render(
            'FoodAppBundle:Default:footer_links.html.twig',
            array(
                'topRatedPlaces' => $topRatedPlaces,
                'staticPages' => $staticPages,
                'cities' => $cityService->getActiveCity()
            )
        );
    }

    public function bannedAction(Request $request)
    {
        $ip = $request->getClientIp();

        $repository = $this->getDoctrine()->getRepository('FoodAppBundle:BannedIp');
        $ipInfo = $repository->findOneBy(array('ip' => $ip));

        return $this->render('FoodAppBundle:Default:banned.html.twig', ['ipInfo' => $ipInfo]);
    }

    /**
     * Thank for subscription to newsletter
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Response
     */
    public function newsletterSubscriptionAction(Request $request)
    {
        switch ($request->getMethod()) {
            case "POST":
                $this->get('food.newsletter')->subscribe($request->get('newsletter_email'), $request->getLocale());
                return $this->redirect($this->generateUrl('food_newsletter_subscribe'), 302);
            default:
                return $this->render('FoodAppBundle:Default:newsletter_subscription.html.twig');

        }
    }

    public function sitemapAction()
    {

        /*
        - visos virtuvės pagal visus galimus miestus (pvz. https://foodout.lt/Vilnius/italiska/ Svarbu, kad būtų segeneruota tik tie URL adresai, kurie turi bent vieną atitinkantį restoraną);
         */
        $availableLocales = $this->container->getParameter('available_locales');
        $availableLocales = array($availableLocales[0]);

        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->findBy(array('active' => 1));

        $staticPages = $this->get('food.static')->getActivePages(30, true);

        $cities = $this->get('food.city_service')->getActiveCity();
        var_dump($cities);

        $citiesKitchens = array();
        foreach ($cities as $city) {
            $citiesKitchens[$city] = $this->get('food.places')->getKitchensByCity($city);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'xml');
        return $this->render(
            'FoodAppBundle:Default:sitemap.xml.twig',
            array(
                'domain' => $this->container->getParameter('domain'),
                'availableLocales' => $availableLocales,
                'places' => $places,
                'cities' => $cities,
                'citiesKitchens' => $citiesKitchens,
                'staticPages' => $staticPages,
            ),
            $response
        );
    }

    public function showVideoAction(Request $request)
    {
        return new Response();
        $cookies = $request->cookies;
        $cookie = $cookies->get('i_saw_video');
        if(empty($cookie) || $cookie!=1) {
            return $this->render(
                'FoodAppBundle:Default:videopopup.js.twig',
                array(
                    'video' => $this->container->getParameter('yt_video')
                )
            );
        } else {
            return new Response();
        }
    }

    public function listBestOffersAction()
    {
        $bestOfferViewOptions = array(
            'hideAllOffersLink' => true,
            'best_offers' => $this->getDoctrine()->getRepository('FoodPlacesBundle:BestOffer')->getActiveOffers()
        );

        $options = array(
            'staticPage' => array(
                'title' => $this->get('translator')->trans('index.all_best_offers'),
                'seoTitle' => '',
                'seoDescription' => '',
                'id' => 'all-best-offers',
                'content' => $this->container->get('templating')
                    ->render('FoodPlacesBundle:Default:all_best_offers.html.twig', $bestOfferViewOptions),
            )
        );

        return $this->render('FoodAppBundle:Static:index.html.twig', $options);
    }
}
