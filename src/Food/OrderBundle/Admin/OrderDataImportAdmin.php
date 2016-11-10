<?php
namespace Food\OrderBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\OrderBundle\Entity\OrderDataImport;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class OrderDataImportAdmin extends FoodAdmin
{
    protected $baseRouteName = 'order_data_import';
    protected $baseRoutePattern = 'order_data_import';

    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'create'));
    }

    public function configureFormFields(FormMapper $form)
    {
        parent::configureFormFields($form);
        $form->add('file', 'file', array('label' => 'admin.order.data_file', 'required' => true));
    }

    public function configureListFields(ListMapper $list)
    {
        parent::configureListFields($list);
        $list->add('date', 'date', []);
    }

    /**
     * @param \Food\OrderBundle\Entity\OrderDataImport $object
     */
    public function prePersist($object)
    {
        parent::prePersist($object);
        $now = new \DateTime();
        $object->setDate($now);
        $currentUser = $this->getContainer()->get('security.context')->getToken()->getUser();
        $object->setUser($currentUser);
    }

    /**
     * @param OrderDataImport $object
     */
    public function postPersist($object)
    {
        parent::postPersist($object);
        $this->getContainer()->get('food.order_data_import_service')->importData($object->getFile());
    }
}
