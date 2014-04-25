<?php

namespace Food\OrderBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Acl\Exception\Exception;

class DefaultController extends Controller
{
    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }

    /**
     * @param $hash
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mobileAction($hash)
    {
        $order = $this->get('food.order')->getOrderByHash($hash);
        if ($this->getRequest()->isMethod('post')) {
            switch($this->getRequest()->get('status')) {
                case 'confirm':
                    $this->get('food.order')->statusAccepted();
                break;
                case 'delay':
                    $this->get('food.order')->statusDelayed();
                break;
                case 'cancel':
                    $this->get('food.order')->statusCanceled();
                break;
                case 'finish':
                    $this->get('food.order')->statusFinished();
                break;
            }
            $this->get('food.order')->saveOrder();
        }
        return $this->render('FoodOrderBundle:Default:mobile.html.twig', array('order' => $order));
    }

    /**
     * @param $hash
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mobileDriverAction($hash)
    {
        $order = $this->get('food.order')->getOrderByHash($hash);
        if ($this->getRequest()->isMethod('post')) {
            switch($this->getRequest()->get('status')) {
                case 'finish':
                    $this->get('food.order')->statusFinished();
                break;
            }
            $this->get('food.order')->saveOrder();
        }
        return $this->render('FoodOrderBundle:Default:mobile-driver.html.twig', array('order' => $order));
    }
}
