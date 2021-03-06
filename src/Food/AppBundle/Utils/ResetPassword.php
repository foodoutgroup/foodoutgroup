<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
use Food\UserBundle\Entity\User;

class ResetPassword
{
    use Traits\Service;

    const RESET_USER_PASSWORD_MAILER_ID = '30013949';

    private $container;

    /**
     * @param Service_con $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

   /**
     * Confirmation email
     *
     * @param string $email
     *
     * @return boolean Return true on success, false on failure to send email.
     */
    public function sendEmail($email)
    {
        $user = $this->service('fos_user.user_manager')
                     ->findUserByUsernameOrEmail($email);

        if (empty($user)) return false;

        if (null === $user->getConfirmationToken()) {
            $tokenGenerator = $this->service('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        // send mail through mailer
        $this->sendMailerEmail($user);

        $user->setPasswordRequestedAt(new \DateTime());
        $this->service('fos_user.user_manager')->updateUser($user);

        return true;
    }

    /**
     * Send password reset email
     *
     * @param User $user
     */
    protected function sendMailerEmail($user)
    {
        $mailer = $this->container->get('food.mailer');

        $url = $this->service('router')
                    ->generate('food_user_resetting_reset_password',
                               array('token' => $user->getConfirmationToken()),
                               true);

        $mailer->setVariables(['password_reset_url' => $url])
               ->setRecipient($user->getEmail(), $user->getEmail())
               ->setId($this->container->getParameter('mailer_user_reset'))
               ->send();
    }
}
