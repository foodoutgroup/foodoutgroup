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
        $request = new FullHpsTransRequest('88185002', 'aXVdnHfZSJmz', '3100900010001911');
        $sender = new Sender($request->xml());
        $response = new FullHpsResponse($sender->send());
        var_dump($response);
        // var_dump($request->query->all());
        // var_dump($request->request->all());
        // --------------------------------
        // $params = new Parameters();
        // $params->set('client', '88185002')
        //        ->set('password', 'aXVdnHfZSJmz')
        //        ->set('order_id', uniqid())
        //        ->set('price', '1')
        //        ->set('transaction_datetime', date('Ymd H:i:s'))
        //        ->set('comment', 'TEST')
        //     //    ->set('return_url', 'http://foodout.lt/return')
        //        ->set('return_url', 'http://localhost:3000/')
        //     //    ->set('expiry_url', 'http://foodout.lt/expire');
        //        ->set('expiry_url', 'http://localhost:3000/');
        //
        // $request = new FullHpsRequest($params);
        // $sender = new Sender($request->xml());
        // $response = new FullHpsResponse($sender->send());
        //
        // var_dump($response->xml());
        // var_dump($response->redirect_url());

        die('xxx');
        // Check if user is not banned
        $ip = $request->getClientIp();
        // Dude is banned - hit him
        if ($this->get('food.app.utils.misc')->isIpBanned($ip)) {
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
        if (!empty($sessionLocation)
            && !empty($sessionLocation['city']) && !empty($sessionLocation['address_orig'])) {
            $formDefaults = array(
                'city' => $sessionLocation['city'],
                'address' => $sessionLocation['address_orig'],
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

        $staticPages = $this->get('food.static')->getActivePages(30, false);

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
}
