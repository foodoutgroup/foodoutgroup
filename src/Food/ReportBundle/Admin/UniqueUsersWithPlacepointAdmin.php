<?php
namespace Food\ReportBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;


class UniqueUsersWithPlacepointAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_unique_users_with_placepoint_admin';
    protected $baseRoutePattern = 'unique-users-and-placepoint';
    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show'));
    }
}





























