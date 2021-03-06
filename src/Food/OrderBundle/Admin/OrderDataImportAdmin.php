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

    protected $datagridValues = array(
        '_sort_order' => 'DESC'
    );

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
        $list->add('id');
        $list->add('date', 'date', []);
        $list->add('username', 'user', []);
        $list->add('infodata', 'infodata', []);
        $list->add('filename');
        $list->add('is_imported', 'boolean');
        $list->add('ordersChanged', 'sonata_type_list', array('admin_code' => 'sonata.admin.order', 'route' => array( 'name' => 'show') ));

    }

    /**
     * @param \Food\OrderBundle\Entity\OrderDataImport $object
     */
    public function prePersist($object)
    {
        if (!$this->getContainer()->get('filesystem')->exists(OrderDataImport::SERVER_PATH_TO_FILE_FOLDER)) {
            $this->getContainer()->get('filesystem')->mkdir(OrderDataImport::SERVER_PATH_TO_FILE_FOLDER);
        }
        parent::prePersist($object);
        $now = new \DateTime();
        $object->setDate($now);

        $object->setUser($this->getUser());
        /*
        $changeLog = $this->getContainer()->get('food.order_data_import_service')->importData($object->getFile(), $object);
        $object->setInfodata(json_encode($changeLog['infodata']));
        foreach ($changeLog['orders'] as $order) {
            $object->getOrdersChanged()->add($this->getContainer()->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($order));
        }
        */
    }
}
