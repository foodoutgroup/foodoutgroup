<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;

class Notifications
{
    use Traits\Service;

    public function init()
    {
        $flashBag = $this->service('session')->getFlashBag();
        $templating = $this->service('templating');

        return $templating->render(
            'FoodAppBundle:Notifications:init.html.twig',
            [
                'noty_success_message' => $this->getSuccessMessage()
            ]
        );
    }

    public function setSuccessMessage($message)
    {
        $this
            ->service('session')
            ->getFlashBag()
            ->set('noty_success_message', $message)
        ;
    }

    public function getSuccessMessage()
    {
        return $this
            ->service('session')
            ->getFlashBag()
            ->get('noty_success_message')
        ;
    }
}
