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

    private $container;

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

    public function setContainer($container)
    {
        $this->container = $container;
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
        if($this->container->getParameter('country') == 'LT'){
            $this->_notifyNewUser($user);
        }

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
    protected function _notifyNewUser($user)
    {
        $ml = $this->container->get('food.mailer');
        $variables = array(
            'name' => $user->getUsername(),
        );
        $mailTemplate = $this->container->getParameter('mailer_notify_new_user');

        $ml->setVariables($variables)
            ->setRecipient($user->getEmail())
            ->setId($mailTemplate)
            ->send();
    }
}
