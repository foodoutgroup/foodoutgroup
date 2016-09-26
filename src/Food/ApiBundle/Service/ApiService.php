<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\MenuItem;
use Food\ApiBundle\Common\Restaurant;
use Food\ApiBundle\Exceptions\ApiException;
use Food\DishesBundle\Entity\FoodCategory;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ApiService extends ContainerAware
{
    /**
     * @return string
     */
    public function getSessionId() {
        return $this->container->get('session')->getId();
    }

    /**
     * @param Place $place
     * @param PlacePoint $placePoint
     * @param bool|false $pickUpOnly
     * @param null|array $locationData
     * @param string|null $deliveryType
     * @return Restaurant
     */
    public function createRestaurantFromPlace($place, $placePoint, $pickUpOnly = false, $locationData = null, $deliveryType = null)
    {
        $restaurant = new Restaurant(null, $this->container);
        return $restaurant->loadFromEntity($place, $placePoint, $pickUpOnly, $locationData, $deliveryType);
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
            $currentWeek = date('W') % 2 == 1; # 1 - odd 0 - even
            $currentTime = date("H:i");
            foreach ($place->getDishes() as $dish) {
                $menuItem = new MenuItem(null, $this->container);
                if ($dish->getActive()) {
                    // Is even check on and this is even week?

                    $timeFrom = $dish->getTimeFrom();
                    $timeTo = $dish->getTimeTo();

                    if (!is_null($timeFrom) && !is_null($timeTo)) {
                        $timeFrom = \DateTime::createFromFormat('H:i', $timeFrom);
                        $timeTo = \DateTime::createFromFormat('H:i', $timeTo);
                        $currentTime = \DateTime::createFromFormat('H:i', date("H:i"));
                        if ($currentTime < $timeFrom || $currentTime > $timeTo) {
                           continue;
                        }
                    }

                    if ($dish->getCheckEvenOddWeek()) {
                        if (($dish->getEvenWeek() && $currentWeek) || (!$dish->getEvenWeek() && !$currentWeek)) {
                            // Skip this dish as it is wrong wee
                            continue;
                        }
                    }

                    // Is time check on and its time to show?
                    $timeFrom = $dish->getTimeFrom();
                    $timeTo = $dish->getTimeTo();
                    if (empty($timeFrom) && !empty($timeTo)) {
                        if (!($currentTime >= $timeFrom && $currentTime <= $timeTo)) {
                            // Skip this dish. It is not the right time to show
                            continue;
                        }
                    }

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
     * @return bool
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

    /**
     * @param int $placeId
     * @param null $updated_at
     * @param $menuItems
     * @return array
     */
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

    /**
     * @param int $placeId
     * @param int $menuItem
     * @return array
     */
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
            $this->container->get('logger')->error("Empty token");
            $this->container->get('logger')->error("Trace: ", print_r(debug_backtrace(2), true));
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

    /**
     * @param User $user
     * @return bool
     * @throws ApiException
     */
    public function isRealEmailSet(User $user)
    {
        if (!$user instanceof User) {
            throw new ApiException('User does not exist', 400, array('error' => 'User does not exist', 'description' => null));
        }

        $email = $user->getEmail();
        $phone = $user->getPhone();
        $facebook_id = $user->getFacebookId();

        if (($email == ($facebook_id.'@foodout.lt')) || ($email == ($phone.'@foodout.lt'))) {
            return false;
        }
        return true;
    }
}
