<?php

namespace Food\OrderBundle\Tests\Controller;

use Food\OrderBundle\Controller\DefaultController;
use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Entity\OrderExtra;

class DefaultControllerTest extends WebTestCase
{
    public function testFormStatusConversion()
    {
        $controller = new DefaultController();

        $formStatus1 = 'confirm';
        $expectedStatus1 = 'accepted';
        $formStatus2 = 'cancel';
        $expectedStatus2 = 'canceled';
        $formStatus3 = 'finish';
        $expectedStatus3 = 'finished';
        $formStatus4 = 'completed';
        $expectedStatus4 = 'completed';
        $formStatus5 = 'compomg_status';
        $expectedStatus5 = '';

        $statusGot1 = $controller->formToEntityStatus($formStatus1);
        $statusGot2 = $controller->formToEntityStatus($formStatus2);
        $statusGot3 = $controller->formToEntityStatus($formStatus3);
        $statusGot4 = $controller->formToEntityStatus($formStatus4);
        $statusGot5 = $controller->formToEntityStatus($formStatus5);

        $this->assertEquals($expectedStatus1, $statusGot1);
        $this->assertEquals($expectedStatus2, $statusGot2);
        $this->assertEquals($expectedStatus3, $statusGot3);
        $this->assertEquals($expectedStatus4, $statusGot4);
        $this->assertEquals($expectedStatus5, $statusGot5);
    }

    public function testRestaurantMobileOrder()
    {
        $orderService = $this->getContainer()->get('food.order');

        $place = $this->getPlace('restaurantMobileTest');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, $orderService::$status_new);

        $orderService->setOrder($order);
        $hash = $orderService->generateOrderHash($order);
        $order->setOrderHash($hash);
        $order->setUser($this->getUser());
        $orderExtra = new OrderExtra();
        $orderExtra->setOrder($order)
            ->setFirstname($this->getUser()->getFirstname())
            ->setLastname($this->getUser()->getLastname())
            ->setEmail($this->getUser()->getEmail())
            ->setPhone($this->getUser()->getPhone());
        $order->setOrderExtra($orderExtra);
        $orderService->saveOrder();

        $this->client->request('GET', '/o/'.$hash.'/');

        $this->assertEquals('Food\OrderBundle\Controller\DefaultController::mobileAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'name="status" value="confirm"') > 0
        );
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'name="status" value="cancel"') > 0
        );
    }

    public function testAdminMobileOrder()
    {
        $orderService = $this->getContainer()->get('food.order');

        $place = $this->getPlace('adminMobileTest');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, $orderService::$status_new);

        $orderService->setOrder($order);
        $hash = $orderService->generateOrderHash($order);
        $order->setOrderHash($hash);
        $order->setUser($this->getUser());
        $orderExtra = new OrderExtra();
        $orderExtra->setOrder($order)
            ->setFirstname($this->getUser()->getFirstname())
            ->setLastname($this->getUser()->getLastname())
            ->setEmail($this->getUser()->getEmail())
            ->setPhone($this->getUser()->getPhone());
        $order->setOrderExtra($orderExtra);
        $orderService->saveOrder();

        $this->client->request('GET', '/o-spr/'.$hash.'/');

        $this->assertEquals('Food\OrderBundle\Controller\DefaultController::mobileAdminAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'name="status" value="confirm"') > 0
        );
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'name="status" value="cancel"') > 0
        );
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'collapsable-row') > 1
        );
    }

    public function testDriverMobileOrder()
    {
        $orderService = $this->getContainer()->get('food.order');

        $place = $this->getPlace('adminMobileTest');
        $placePoint = $this->getPlacePoint($place);
        $order = $this->getOrder($place, $placePoint, $orderService::$status_new);

        $orderService->setOrder($order);
        $hash = $orderService->generateOrderHash($order);
        $order->setOrderHash($hash);
        $order->setUser($this->getUser());
        $orderExtra = new OrderExtra();
        $orderExtra->setOrder($order)
            ->setFirstname($this->getUser()->getFirstname())
            ->setLastname($this->getUser()->getLastname())
            ->setEmail($this->getUser()->getEmail())
            ->setPhone($this->getUser()->getPhone());
        $order->setOrderExtra($orderExtra);
        $orderService->saveOrder();

        $this->client->request('GET', '/d/'.$hash.'/');

        $this->assertEquals('Food\OrderBundle\Controller\DefaultController::mobileDriverAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'name="status" value="confirm"') == 0
        );
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'name="status" value="cancel"') == 0
        );
        $this->assertTrue(
            strpos($this->client->getResponse()->getContent(), 'collapsable-row') == 0
        );
    }
}
