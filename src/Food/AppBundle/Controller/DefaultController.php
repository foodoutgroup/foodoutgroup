<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FoodAppBundle:Default:index.html.twig');
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
