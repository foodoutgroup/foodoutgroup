<?php
namespace Food\UserBundle\Service;

use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;

class UserService extends ContainerAware {
    use Traits\Service;

    private $_discount = null;

    /**
     * @param User $user
     * @return int|mixed|null|string
     */
    public function getDiscount(User $user)
    {
        if (is_null($this->_discount)) {
            // jei naudotojo registracija egzistuoja
            if ($user->getCreatedAt() instanceof \DateTime) {
                $userCreatedPlusMonth = clone $user->getCreatedAt();
                $userCreatedPlusMonth->modify('+1 month');
                $firstMonthDiscount = $this->container->getParameter('b2b_first_month_discount');
            } else {
                $userCreatedPlusMonth = new \DateTime();
                $firstMonthDiscount = false;
            }

            $userRepo = $this->container->get('doctrine')->getRepository('FoodUserBundle:User');

            // jei prie customer yra nurodyta nuolaida
            if ($user->getDiscount() > 0) {
                $this->_discount = $user->getDiscount();

            // jei galioja pirmo men nuolaida ir menuo nepraejo
            // @TODO apriboti iki menesio
            } elseif ($firstMonthDiscount && $userCreatedPlusMonth > new \DateTime()) {
                $this->_discount = $this->container->getParameter('b2b_first_month_discount_percent');

            // jei menuo praejo, gaunam nuolaida is range, jei range nera, tada 0
            } else {
                $this->_discount = $userRepo->getDiscountByRange($user, $firstMonthDiscount, $userCreatedPlusMonth) ?: 0;
            }

            if ($this->_discount * 10 % 10 == 0) {
                $this->_discount = (int) $this->_discount;
            }
        }

        return $this->_discount;
    }
}
