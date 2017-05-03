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

        $this->container->get('slug')->checkNotFound();

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

                $cityObj = $defaultUserAddress->getCityId();
                $formDefaults = [
                    'city_id' => $cityObj ? $cityObj->getId() : null,
                    'address' => $addressData['street'],
                    'house' => $addressData['house'],
                    'flat' => $addressData['flat'],
                ];
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
        return $this->render('FoodAppBundle:Default:footer_links.html.twig', [
                'topRatedPlaceCollection' =>  $this->get('food.places')->getTopRatedPlaces(12),
                'staticPageCollection' => $this->get('food.static')->getActivePages(60),
                'cities' => $this->get('food.city_service')->getActiveCity()
            ]
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
                return $this->redirect($this->get('slug')->generateURL('food_newsletter_subscribe'), 302);
            default:
                return $this->render('FoodAppBundle:Default:newsletter_subscription.html.twig');

        }
    }

}
