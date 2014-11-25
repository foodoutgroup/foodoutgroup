<?php

namespace Food\UserBundle\Service;

use FOS\UserBundle\Form\Handler\RegistrationFormHandler as OriginalHandler;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class RegistrationFormHandler extends OriginalHandler
{
    protected $user;
    protected $translator;
    protected $notifications;
    protected $foodMailer;
    protected $router;
    protected $cart;

    public function __construct(FormInterface $form,
                                Request $request,
                                UserManagerInterface $userManager,
                                MailerInterface $mailer,
                                TokenGeneratorInterface $tokenGenerator,
                                $translator,
                                $notifications,
                                $foodMailer,
                                $router,
                                $cart)
    {
        parent::__construct($form,
                            $request,
                            $userManager,
                            $mailer,
                            $tokenGenerator);

        $this->translator = $translator;
        $this->notifications = $notifications;
        $this->foodMailer = $foodMailer;
        $this->router = $router;
        $this->cart = $cart;
    }

    /**
     * @param boolean $confirmation
     */
    public function process($confirmation = false)
    {
        $existingUser = $this->getUser();
        $fullyRegistered = !!\Maybe($existingUser)->getFullyRegistered()
                                                  ->val(false);

        $user = $this->createUser();
        
        if ($existingUser && !$fullyRegistered) {
            $user = $existingUser;
        }

        $user->setUsername($user->getEmail());
        $user->setEnabled(true);

        $this->form->setData($user);

        if ('POST' === $this->request->getMethod()) {
            $this->form->bind($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($user, $confirmation);

                return true;
            }
        }

        return false;
    }

    /**
     * @param boolean $confirmation
     */
    protected function onSuccess(UserInterface $user, $confirmation)
    {
        $user->setEnabled(true);
        $user->setFullyRegistered(true);

        // set noty notification for successful user registration
        $this->notifications->setSuccessMessage(
            $this->translator->trans('general.successful_user_registration'));

        // $d = $this->request->get('fos_user_registration_form');
        // $this->_notifyNewUser($user, $d['plainPassword']['first']);

        $this->userManager->updateUser($user);
    }

    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;
    }

    protected function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @param $pass
     */
    protected function _notifyNewUser($user, $pass)
    {
        $variables = [
            'username' => $user->getUsername(),
            'password' => $pass,
            'login_url' => $this->router
                                ->generate('food_lang_homepage', [], true)
        ];

        $this->foodMailer
             ->setVariables($variables)
             ->setRecipient($user->getEmail(),
                            $user->getEmail())
             ->setId(30009253)
             ->send();
    }
}
