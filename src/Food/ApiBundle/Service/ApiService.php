<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\MenuItem;
use Food\ApiBundle\Common\Restaurant;
use Food\ApiBundle\Exceptions\ApiException;
use Food\DishesBundle\Entity\FoodCategory;
use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ApiService extends ContainerAware
{
    public function getSessionId() {
        return $this->container->get('session')->getId();
    }
    public function createRestaurantFromPlace($place, $placePoint, $pickUpOnly = false)
    {
        $restaurant = new Restaurant(null, $this->container);
        return $restaurant->loadFromEntity($place, $placePoint, $pickUpOnly);
    }

    /**
     * @param $placeId
     * @param string $updated_at
     * @return array
     */
    public function createMenuByPlaceId($placeId, $updated_at = null)
    {
        if (empty($placeId)) {
            return array();
        }
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        if ($place) {
            $returner = array();
            foreach ($place->getDishes() as $dish) {
                $menuItem = new MenuItem(null, $this->container);
                if ($dish->getActive()) {
                    if ($this->_hasAnyActiveCats( $dish->getCategories())) {
                        $item = $menuItem->loadFromEntity($dish);
                        //if (!empty($item)) {
                        //    if ($updated_at == null || $item['updated_at'] > $updated_at)
                            $returner[] = $item;
                        //}
                    }
                }
            }
            return $returner;
        }
        return array();
    }

    /**
     * @param FoodCategory[] $categories
     */
    private function _hasAnyActiveCats($categories)
    {
        foreach ($categories as $cat) {
            if ($cat->getActive()) {
                return true;
            }
        }
        return false;
    }

    public function createDeletedByPlaceId($placeId, $updated_at = null, &$menuItems)
    {
        $query = "SELECT id,photo, deleted_at  FROM dish WHERE place_id=".intval($placeId)." AND (active=0 OR deleted_at IS NOT NULL)";
        $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($query);

        $stmt->execute();
        $dishes = $stmt->fetchAll();
        if (!empty($dishes)) {
            $returner = array();
            foreach ($dishes as $wtf) {
                $returner[] = $wtf['id'];
                $menuItems[] = array(
                    'item_id ' => intval($wtf['id']),
                    'restaurant_id' => intval($placeId),
                    'category_id' => array(),
                    'status' => 'deleted',
                    'thumbnail_url' => (!empty($wtf['photo']) ? 'uploads/dishes/'.$wtf['photo'] : null),
                    'updated_at' => date("U", strtotime($wtf['deleted_at']))
                );
            }
            return $returner;
        }
        /*
        $place = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        if ($place) {
            $returner = array();
            foreach ($place->getDishes() as $dish) {
                $menuItem = new MenuItem(null, $this->container);
                $item = $menuItem->loadFromEntity($dish);
                //if (!empty($item)) {
                //    if ($updated_at == null || $item['updated_at'] > $updated_at)
                $returner[] = $item;
                //}
            }
            return $returner;
        }
        */
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
     * @throws \Food\ApiBundle\Exceptions\ApiException
     */
    public function loginByHash($hash)
    {
        if (empty($hash)) {
            throw new ApiException('Empty token', 400, array('error' => 'Token is empty', 'description' => null));
        }

        $um = $this->container->get('fos_user.user_manager');
        $security = $this->container->get('security.context');

        $token = $security->getToken();
        if ($token instanceof TokenInterface) {
            $currentUser = $token->getUser();
        } else {
            $currentUser = null;
        }

        $user = $um->findUserBy(array('apiToken' => $hash));

        if (!$user instanceof User) {
            throw new ApiException('Token does not exist', 400, array('error' => 'Token does not exist', 'description' => null));
        }
        if (!$user->getApiTokenValidity()) {
            throw new ApiException('Token does not exist', 400, array('error' => 'Token does not exist', 'description' => null));
        }
        if ($user->getApiTokenValidity()->getTimestamp() < time() ) {
            throw new ApiException('User token has expired', 400, array('error' => 'User token has expired', 'description' => null));
        }

        // Refresh the token
        $user->setApiTokenValidity(new \DateTime('+1 year'));
        $um->updateUser($user);

        // User not in security session - set him
        if (!$currentUser instanceof User || $currentUser->getId() != $user->getId()) {
            $providerKey = $this->container->getParameter('fos_user.firewall_name');
            $roles = $user->getRoles();
            $token = new UsernamePasswordToken($user, null, $providerKey, $roles);
            $security->setToken($token);
        }
    }
}