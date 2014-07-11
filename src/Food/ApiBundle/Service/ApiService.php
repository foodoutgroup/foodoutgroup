<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\MenuItem;
use Food\ApiBundle\Common\Restaurant;
use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ApiService extends ContainerAware
{
    public function getSessionId() {
        return $this->container->get('session')->getId();
    }
    public function createRestaurantFromPlace($place, $placePoint)
    {
        $restaurant = new Restaurant(null, $this->container);
        return $restaurant->loadFromEntity($place, $placePoint);
    }

    public function createMenuByPlaceId($placeId)
    {
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        if ($place) {
            $returner = array();
            foreach ($place->getDishes() as $dish) {
                $menuItem = new MenuItem(null, $this->container);
                $item = $menuItem->loadFromEntity($dish);
                if (!empty($item)) {
                    $returner[] = $item;
                }
            }
            return $returner;
        }
        return array();
    }

    public function createMenuItemByPlaceIdAndItemId($placeId, $menuItem)
    {
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        $dish = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Dish')->find((int)$menuItem);
        $menuItem = new MenuItem(null, $this->container);
        return $menuItem->loadFromEntity($dish, true);
    }

    /**
     * @param User $user
     * @return string
     */
    public function generateUserHash(User $user)
    {
        $hash = md5($user->getId().'-'.time());
        return $hash;
    }

    /**
     * @param string $hash
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function loginByHash($hash)
    {
        /**
         * TODO
         *  - tikrinam ar joks useris nepriloginas. Jei prilogintas - sulyginam tokenus ir be reikalo antra karta logino nevykdom
         */

        $um = $this->container->get('fos_user.user_manager');
        $security = $this->container->get('security.context');

        $user = $um->findUserBy(array('apiToken' => $hash));

        if (!$user instanceof User) {
            throw new NotFoundHttpException('Token does not exist');
        }

        if ($user->getApiTokenValidity()->getTimestamp() < time() ) {
            throw new NotFoundHttpException('User token has expired');
        }

        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $roles = $user->getRoles();
        $token = new UsernamePasswordToken($user, null, $providerKey, $roles);
        $security->setToken($token);
    }
}