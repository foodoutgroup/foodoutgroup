<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
use Symfony\Component\DependencyInjection\Container;

class ResetPassword
{
    use Traits\Service;

    /**
     * @return boolean Return true on success, false on failure to send email.
     */
    public function sendEmail($email)
    {
        $user = $this->service('fos_user.user_manager')
                     ->findUserByUsernameOrEmail($email);

        if (empty($user)) return false;

        $tokenTtl = $this->container()
                         ->getParameter('fos_user.resetting.token_ttl');

        if ($user->isPasswordRequestNonExpired($tokenTtl)) return false;

        if (null === $user->getConfirmationToken()) {
            $tokenGenerator = $this->service('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $this->service('fos_user.mailer')->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->service('fos_user.user_manager')->updateUser($user);

        // return new RedirectResponse($this->service('router')->generate('fos_user_resetting_check_email',
        //     array('email' => $this->getObfuscatedEmail($user))
        // ));

        return true;
    }
}
