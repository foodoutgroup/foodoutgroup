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
        );

        $user = $this->getUser();

        if ($user instanceof User) {
            $defaultUserAddress = $user->getDefaultAddress();
            if (!empty($defaultUserAddress)) {
                $addressData = $miscUtils->parseAddress($defaultUserAddress->getAddress());

                $formDefaults = array(
                    'city' => $defaultUserAddress->getCity(),
                    'address' => $addressData['street'],
                    'house' => $addressData['house']
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
                'house' => $addressData['house']
            );
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Response
     */
    public function newsletterSubscribeAction(Request $request)
    {
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

    public function sitemapAction()
    {
        $availableLocales = $this->container->getParameter('available_locales');

        // TODO kolkas ijungta tik viena, tad..
        $availableLocales = array($availableLocales[0]);

        $places = $this->getDoctrine()->getManager()->getRepository('FoodDishesBundle:Place')
            ->findBy(array('active' => 1));

        $staticPages = $this->get('food.static')->getActivePages(30, true);

        $response = new Response();
        $response->headers->set('Content-Type', 'xml');
        return $this->render(
            'FoodAppBundle:Default:sitemap.xml.twig',
            array(
                'domain' => $this->container->getParameter('domain'),
                'availableLocales' => $availableLocales,
                'places' => $places,
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
                    'video' => 'https://www.youtube.com/v/3zFW6hnuvJY?fs=1&amp;autoplay=1'
                )
            );
        } else {
            return new Response();
        }
    }

    public function meetAction()
    {
        return $this->render(
            'FoodAppBundle:Default:meet.html.twig'
        );
    }
}
