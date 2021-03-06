<?php

namespace Food\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Form;
use Doctrine\Common\Collections\ArrayCollection;
use Food\UserBundle\Form\Type\ProfileFormType;
use Food\UserBundle\Form\Type\ProfileMegaFormType;
use Food\UserBundle\Form\Type\UserAddressFormType;
use Food\UserBundle\Form\Type\ChangePasswordFormType;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Food\OrderBundle\Service\OrderService;

class DefaultController extends Controller
{
    /**
     * @Route("/register/create", name="user_register_create")
     * @Template()
     * @Method("POST")
     */
    public function registerCreateAction(Request $request)
    {
        $form = $this->container->get('fos_user.registration.form');
        // we will be using extended version of fos registration form handler
        // $formHandler = $this->container->get('fos_user.registration.form.handler');
        $formHandler = $this->container->get('food_user.registration.form.handler');

        // we have no confirmation email, hence this parameter is always false
        // $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
        $confirmationEnabled = false;

        // check if user exists
        $email = $request->request->get('fos_user_registration_form[email]', '', true);
        $existingUser = $this->userExists($email);

        $formHandler->setUser($existingUser);
        $process = $formHandler->process($confirmationEnabled);

        if ($process) {
            return new Response('');
        }

        return $this->render(
            'FoodUserBundle:Default:register.html.twig',
            [
                'form' => $form->createView(),
                'errors' => $this->formErrors($form),
                'submitted' => $form->isSubmitted(),
                'isBussinesClient' => $form->get('isBussinesClient')->getData()
            ]
        );
    }

    /**
     * @Route("/register", name="user_register")
     * @Template("FoodUserBundle:Default:register.html.twig")
     */
    public function registerAction(Request $request)
    {
        $form = $this->container->get('fos_user.registration.form');

        return $this->render(
            'FoodUserBundle:Default:register.html.twig',
            [
                'form' => $form->createView(),
                'errors' => $this->formErrors($form),
                'submitted' => $form->isSubmitted(),
                'isBussinesClient' => $form->get('isBussinesClient')->getData()
            ]
        );
    }

    /**
     * @Route("/login", name="user_login")
     * @Template("FoodUserBundle:Default:login.html.twig")
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();
        $session->set('session_id_before_login', $session->getId());

        $csrfToken = $this->container->has('form.csrf_provider')
            ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate')
            : null;
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);

        return $this->render(
            'FoodUserBundle:Default:login.html.twig',
            [
                'csrf_token' => $csrfToken,
                'last_username' => $lastUsername
            ]
        );
    }

    /**
     * @Route("/profile/update", name="user_profile_update")
     * @Template("FoodUserBundle:Default:profile.html.twig")
     * @Method("POST")
     */
    public function profileUpdateAction(Request $request)
    {
        // services
        $userManager = $this->container->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');
        $flashbag = $this->get('session')->getFlashBag();

        // data
        $user = $this->user();


        // embedded form
        $requestData = $request->request->get('food_user_profile');

        // mega form containts 3 embedded forms
        $form = $this->createProfileMegaForm($user, $requestData['change_password']['current_password']);
        $form->handleRequest($request);
        $countryCode = $form->get('profile')->get('countryCode')->getData();
        $countryNumber = $this->container->get('food.phones_code_service')->getByCountry($countryCode)[0]->getCode();
        $profilePhone = $form->get('profile')->get('phone')->getData();

        $phoneValidation = $this->container->get('food.phones_code_service')->validatePhoneNumber($profilePhone, $countryCode);

        $formError = [];
        if (!$phoneValidation || isset($phoneValidation[0])) {
            $formError = $this->formErrors($form->get('profile'));
            $formError['phone'] = $translator->trans($phoneValidation[0]);
        } else {

            $lService = $this->get('food.location');
            $addressData = $request->request->get('address');
            $oldLocation = $lService->get();
            if (!empty($addressData['id'])) {
                $address = $this->getDoctrine()->getRepository('FoodUserBundle:UserAddress')->getDefault($user);
                if(!$address || $address->getAddressId() != $addressData['id'] || $address->getFlat() != $addressData['flat']) {
                    $locationFindByString = $lService->findByAddress($addressData['autocomplete']);
                    // jei tikslumas geras ir miestas rastas sistemoje bandom saugot :)
                    if($locationFindByString['precision'] == 0 && !is_null($locationFindByString['city_id'])) {
                        $flat = trim($addressData['flat']);
                        if(empty($flat)) {
                            $flat = null;
                        }
                        $lService->set($locationFindByString, $flat);
                        $lService->saveAddressFromArrayToUser($lService->get(), $user);
                    } else {
                        $lService->set($oldLocation);
                    }
                }
            }

            // password validation
            if ($form->get('change_password')->isValid() && $form->isValid()) {
                $userManager->updateUser($user);
            }


            if ($form->isValid()) {
                $em->flush();
                $flashbag->set('profile_updated', $translator->trans('general.noty.profile_updated'));
                return $this->redirect($this->generateUrl('user_profile'));

            }
        }
        return [
            'form' => $form->createView(),
            'profile_errors' => $formError,
            'change_password_errors' => $this->formErrors($form->get('change_password')),
            'orders' => $this->get('food.order')->getUserOrders($user),
            'submitted' => $form->isSubmitted(),
            'user' => $user,
            'discount' => $this->get('food.user')->getDiscount($this->user()),
            'location' => $this->get('food.location')->get(),
            'addressDefault' => $this->getDoctrine()->getRepository('FoodUserBundle:UserAddress')->getDefault($user) ?: null,
        ];
    }

    /**
     * @Route("/profile/{tab}", name="user_profile", defaults={"tab" = ""})
     * @Template("FoodUserBundle:Default:profile.html.twig")
     */
    public function profileAction($tab)
    {
        // services
        $security = $this->get('security.context');
        $flashbag = $this->get('session')->getFlashBag();

        // page is accessible only to signed in users
        if (!$security->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('food_lang_homepage'));
        }

        // data
        $user = $this->user();

        $form = $this->createProfileMegaForm($user, '');

        return [
            'form' => $form->createView(),
            'profile_errors' => $this->formErrors($form->get('profile')),
            'change_password_errors' => $this->formErrors($form->get('change_password')),
            'tab' => $tab,
            'addressDefault' => $this->getDoctrine()->getRepository('FoodUserBundle:UserAddress')->getDefault($user) ?: null,
            'orders' => $this->get('food.order')->getUserOrders($user),
            'submitted' => $form->isSubmitted(),
            'profile_updated' => $flashbag->get('profile_updated'),
            'profile_update_errors' => $flashbag->get('profile_update_errors'),
            'user' => $this->user(),
            'discount' => $this->get('food.user')->getDiscount($this->user())
        ];
    }

    public function loginButtonAction()
    {
        // due to mystery I will do stuff my way
        $user = $this->user();

        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->render(
                'FoodUserBundle:Default:profile_button.html.twig',
                array('user' => $user)
            );
        }

        return $this->render('FoodUserBundle:Default:login_button.html.twig');
    }

    private function userExists($email)
    {
        $existingUser = new ArrayCollection(
            $this
                ->getDoctrine()
                ->getRepository('FoodUserBundle:User')
                ->findByEmail($email)
        );

        return \Maybe($existingUser)->first()->val(null, true);
    }

    private function user()
    {
        $sc = $this->get('security.context');

        if (!$sc->isGranted('ROLE_USER')) {
            return null;
        }

        return $sc->getToken()->getUser();
    }

    private function createProfileMegaForm($user, $currentPassword)
    {


        $type = new ProfileMegaFormType(
            new ProfileFormType(get_class($user), $this->container->get('food.phones_code_service'), $this->container->getParameter('country'), $user),
            new ChangePasswordFormType(get_class($user), $currentPassword)
        );
        $data = array(
            'profile' => $user,
            'change_password' => $user
        );

        return $this->createForm($type, $data);
    }

    private function formErrors(Form $form)
    {
        $errors = array();

        foreach ($form->all() as $element) {
            $array = $element->getName() != 'plainPassword' ? $element->getErrors() : $element->get('first')->getErrors();
            $callback = function ($carry, $item) {
                $carry[] = $item->getMessage();
                return $carry;
            };
            $initial = [];

            $errors[$element->getName()] = implode('. ', array_reduce($array, $callback, $initial));
        }

        return $errors;
    }
}
