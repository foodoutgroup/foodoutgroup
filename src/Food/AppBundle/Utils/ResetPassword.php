<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
use Symfony\Component\DependencyInjection\Container;

class ResetPassword
{
    use Traits\Service;

    const RESET_USER_PASSWORD_MAILER_ID = '30013949';

    /**
     * @return boolean Return true on success, false on failure to send email.
     */
    public function sendEmail($email)
    {
        $user = $this->service('fos_user.user_manager')
                     ->findUserByUsernameOrEmail($email);

        if (empty($user)) return false;

        // send mail through mailer
        $this->sendMailerEmail($user);

        if (null === $user->getConfirmationToken()) {
            $tokenGenerator = $this->service('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $user->setPasswordRequestedAt(new \DateTime());
        $this->service('fos_user.user_manager')->updateUser($user);

        return true;
    }

    protected function sendMailerEmail($user)
    {
        $mailer = $this->container->get('food.mailer');

        $url = $this->service('router')
                    ->generate('food_user_resetting_reset_password',
                               array('token' => $user->getConfirmationToken()),
                               true);

        $mailer->setVariables(['password_reset_url' => $url])
               ->setRecipient($user->getEmail(), $user->getEmail())
               ->setId(static::RESET_USER_PASSWORD_MAILER_ID)
               ->send();
    }
}
