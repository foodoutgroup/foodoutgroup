<?php

namespace Food\AppBundle\Controller;

use Food\AppBundle\Entity\Slug;
use Food\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pirminis\Gateway\Swedbank\FullHps\Request as FullHpsRequest;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {

        $miscUtils = $this->get('food.app.utils.misc');
        // Check if user is not banned
        $ip = $request->getClientIp();
        // Dude is banned - hit him
        if ($miscUtils->isIpBanned($ip)) {
            return $this->redirect($this->get('slug')->urlFromParam('page_banned', Slug::TYPE_PAGE), 302);
        }

        $formDefaults = array(
            'city_id' => '',
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
                    'city_id' => $defaultUserAddress->getCityId()->getId(),
                    'address' => $addressData['street'],
                    'house' => $addressData['house'],
                    'flat' => $addressData['flat'],

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
                'flat' => $addressData['flat'],
                'city_id' => $sessionLocation['city_id']
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
                return $this->redirect($this->get('slug')->generateURL('food_newsletter_subscribe', 'food_newsletter_subscribe_locale'), 302);
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

        $placeCollection = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')->findBy(array('active' => 1));

        $staticPageCollection = $this->get('food.static')->getActivePages(30, true);

        $cityCollection = $this->get('food.city_service')->getActiveCity();

        $cityKitchenCollection = [];
        foreach ($cityCollection as $city) {
            $cityKitchenCollection[$city] = $this->get('food.places')->getKitchensByCity($city);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'xml');
        return $this->render(
            'FoodAppBundle:Default:sitemap.xml.twig',
            array(
                'domain' => str_replace(["/app_dev.php/"], "", $this->container->getParameter('domain')),
                'availableLocales' => $availableLocales,
                'places' => $placeCollection,
                'cities' => $cityCollection,
//                'citiesKitchens' => $citiesKitchens,
                'staticPages' => $staticPageCollection,
            ),
            $response
        );
    }

}
