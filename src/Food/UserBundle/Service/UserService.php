<?php
namespace Food\UserBundle\Service;

use Food\OrderBundle\Entity\OrderExtra;
use Food\OrderBundle\Service\OrderService;
use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\AppBundle\Traits;

class UserService extends ContainerAware
{
    use Traits\Service;

    private $_discount = null;

    /**
     * @param User $user
     *
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
                $this->_discount = (int)$this->_discount;
            }
        }

        return $this->_discount;
    }

    /**
     * @param OrderExtra[] $orderExtraCollection
     * @param array        $info
     *
     * @return array
     */
    public function getInfoForCrm($orderExtraCollection, array &$info)
    {
        $info['totalOrders'] = count($orderExtraCollection);

        foreach ($orderExtraCollection as $orderExtra) {
            $this->_fillUserInfo($orderExtra, $info);
            $this->_fillOrderInfo($orderExtra, $info);
            break;
        }

        $this->_fillAddressInfo($orderExtraCollection, $info);

        return $info;
    }

    /**
     * @param OrderExtra $orderExtra
     * @param            $info
     */
    private function _fillUserInfo(OrderExtra $orderExtra, array &$info)
    {
        $user = $orderExtra->getOrder()->getUser();
        $rfm = $this->getDoctrine()->getRepository('FoodReportBundle:Rfm')->findOneBy(['userId' => $user->getId()]);
        $status = $this->getDoctrine()->getRepository('FoodReportBundle:RfmStatus')->getStatusByRfm($rfm ? $rfm->getTotalRfmScore() : 0);
        if (!$status) {
            $status = $rfm ? $rfm->getTotalRfmScore() : 0;
        }

        $info['user']['userId'] = $user->getId();
        $info['user']['rfm'] = $status;
        $info['user']['firstname'] = $orderExtra->getFirstname();
        $info['user']['lastname'] = $orderExtra->getLastname();
        $info['user']['phone'] = $orderExtra->getPhone();
        $info['user']['email'] = $orderExtra->getEmail();
        $info['user']['b2b'] = $user->getIsBussinesClient();
    }

    /**
     * @param OrderExtra $orderExtra
     * @param array      $info
     */
    private function _fillOrderInfo(OrderExtra $orderExtra, array &$info)
    {
        $user = $orderExtra->getOrder()->getUser();
        $info['order']['completed'] = 0;
        $info['order']['canceled'] = 0;

        foreach ($user->getOrder() as $order) {
            switch ($order->getOrderStatus()) {
                case OrderService::$status_completed:
                case OrderService::$status_finished:
                    ++$info['order']['completed'];
                    break;
                case OrderService::$status_canceled:
                case OrderService::$status_failed:
                    ++$info['order']['canceled'];
                    break;
            }
        }
    }

    /**
     * @param OrderExtra[] $orderExtraCollection
     * @param array        $info
     */
    private function _fillAddressInfo($orderExtraCollection, array &$info)
    {
        $addresses = [];
        foreach ($orderExtraCollection as $orderExtra) {
            $address = $orderExtra->getOrder()->getAddressId();
            if (count($addresses) < 3 && $address && !in_array($address->getId(), $addresses)) {
                $addresses[] = $address->getId();
                $info['address'][] = $address->getAddress() . ', ' . $address->getCity();
            }
        }
    }
}
