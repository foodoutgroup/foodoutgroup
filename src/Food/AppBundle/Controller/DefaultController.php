<?php

namespace Food\AppBundle\Controller;

use Food\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        // Check if user is not banned
        $ip = $request->getClientIp();
        $repository = $this->getDoctrine()->getRepository('FoodAppBundle:BannedIp');
        $isBanned = $repository->findOneBy(array('ip' => $ip, 'active' => true));
        // Dude is banned - hit him
        if ($isBanned) {
            return $this->redirect($this->generateUrl('banned'), 302);
        }

        $formDefaults = array(
            'city' => '',
            'address' => '',
        );

        $user = $this->getUser();

        if ($user instanceof User) {
            $defaultUserAddress = $user->getDefaultAddress();
            if (!empty($defaultUserAddress)) {
                $formDefaults = array(
                    'city' => $defaultUserAddress->getCity(),
                    'address' => $defaultUserAddress->getAddress(),
                );
            }
        }

        // Lets check session for last used address and use it
        $sessionLocation = $this->get('food.googlegis')->getLocationFromSession();
        if (!empty($sessionLocation)) {
            if (!empty($sessionLocation['city']) && !empty($sessionLocation['address_orig'])) {
                $formDefaults = array(
                    'city' => $sessionLocation['city'],
                    'address' => $sessionLocation['address_orig'],
                );
            }
        }

        return $this->render(
            'FoodAppBundle:Default:index.html.twig',
            array(
                'formDefaults' => $formDefaults
            )
        );
    }

    public function footerAction()
    {
        $topRatedPlaces = $this->get('food.places')->getTopRatedPlaces(10);
        $staticPages = $this->get('food.static')->getActivePages(10);
        return $this->render(
            'FoodAppBundle:Default:footer_links.html.twig',
            array(
                'topRatedPlaces' => $topRatedPlaces,
                'staticPages' => $staticPages,
            )
        );
    }

    public function bannedAction(Request $request)
    {
        $ip = $request->getClientIp();

        $repository = $this->getDoctrine()->getRepository('FoodAppBundle:BannedIp');
        $ipInfo = $repository->findOneBy(array('ip' => $ip));

        return $this->render(
            'FoodAppBundle:Default:banned.html.twig',
            array(
                'ipInfo' => $ipInfo
            )
        );
    }

    /**
     * Subscribtion to newsletter
     * @return Response
     */
    public function newsletterSubscribeAction()
    {
        $request = $this->get('request');
        $newsleterEmail = $request->get('newsletter_email');

        $this->get('food.newsletter')->subscribe($newsleterEmail, $request->getLocale());

        // Pagal visa tvarka, po posto - turi but redirectas
        return $this->redirect($this->generateUrl('food_newsletter_thank'), 302);
    }

    /**
     * Thank for subscribtion to newsletter
     * @return Response
     */
    public function newsletterThankAction()
    {
        return $this->render('FoodAppBundle:Default:newsletter_subscribtion.html.twig');
    }
}
