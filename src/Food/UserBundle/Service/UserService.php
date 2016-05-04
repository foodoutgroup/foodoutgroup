<?php
namespace Food\UserBundle\Service;

use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;

class UserService extends ContainerAware {
    use Traits\Service;

    private $_discount = null;

    public function getDiscount(User $user)
    {
        if (is_null($this->_discount)) {
            $userCreatedPlusMonth = clone $user->getCreatedAt();
            $userCreatedPlusMonth->modify('+1 month');
            $em = $this->container->get('doctrine.orm.entity_manager');
            $userRepo = $em->getRepository('FoodUserBundle:User');
            $firstMonthDiscount = $this->container->getParameter('b2b_first_month_discount');

            // jei prie customer yra nurodyta nuolaida
            if ($user->getDiscount() > 0) {
                $this->_discount = $user->getDiscount();

            // jei galioja pirmo men nuolaida ir menuo nepraejo
            } elseif ($firstMonthDiscount && $userCreatedPlusMonth > new \DateTime()) {
                $this->_discount = $this->container->getParameter('b2b_first_month_discount_percent');

            // jei menuo praejo, gaunam nuolaida is range, jei range nera, tada 0
            } else {
                $this->_discount = $userRepo->getDiscountByRange($user, $firstMonthDiscount, $userCreatedPlusMonth) ?: 0;
            }
        }

        return $this->_discount;
    }
}
