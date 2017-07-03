<?php
namespace Food\AppBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;


class ImportAdmin extends SonataAdmin
{
    protected $baseRouteName = 'food_import_admin';
    protected $baseRoutePattern = 'import';
    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection
            ->add('processImport');
    }
}





























