<?php

namespace Food\AppBundle\Controller;

use Food\AppBundle\Entity\MarketingUser;
use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Service\PaySera;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\True;


class GameController extends Controller
{
    public function indexAction(Request $request)
    {
        $data = array(
            'showSuccess' => false,
            'showError' => false,
        );

        $participant = new MarketingUser();
        $participant->setCreatedAt(new \DateTime("now"));

        $emptyFieldMessage = $this->get('translator')->trans('food.game.empty_field');

        $form = $this->createFormBuilder($participant)
            ->add('firstName', 'text', array(
                'label' => 'food.game.firstname',
                'required' => true,
                // This error applies to all fields
                'attr' => array('oninvalid'=>"setCustomValidity('".$emptyFieldMessage."')")))
            ->add('lastName', 'text', array('label' => 'food.game.lastname'))
            ->add('city', 'text', array('label' => 'food.game.city', 'required' => true))
            ->add('birthDate', 'date', array('label' => 'food.game.bdate'))
            ->add('phone', 'text', array(
                'label'=>'food.game.phone',
                'attr' => array('placeholder' => '370XXXXXXX'),
                'required' => true
            ))
            ->add('email', 'text',array('label' => 'food.game.email', 'required' => true))
            ->add('aggree', 'checkbox', array(
                'mapped' => false,
//                'required' => true,
                'label' => 'food.game.agree',
                'constraints' => new True(array(
                    'message' => 'sutik, padla',
                )),
                'attr' => array('checked' => ''),
            ))
            ->add('save', 'submit', array('label' => 'food.game.register'))
            ->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {
            // Do the save
            $em = $this->container->get('doctrine')->getManager();

            $em->persist($participant);
            $em->flush();

            $data['showSuccess'] = true;
        } else if ($request->isMethod('get')) {
            $data['showError'] = false;
        } else {
            $data['showError'] = true;
        }

        $data['form'] = $form->createView();

        $textData = $this->container->get('food.dishes.utils.slug')->getOneByName('game_text', $this->get('request')->getLocale());
        if (empty($textData)) {
            $data['content'] = "Sukurt puslapi game_text";
        } else {
            $textItem = $this->container->get('doctrine')->getManager()->getRepository('FoodAppBundle:StaticContent')->find($textData->getItemId());
            $data['content'] = $textItem->getContent();
        }

        return $this->render(
            '@FoodApp/Default/game.html.twig',
            $data
        );
    }
}