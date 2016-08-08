<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class ParamAdmin extends FoodAdmin
{

    /**
     * Fields to be shown on filter forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('param', null, array('label' => 'admin.param.name'))
        ;
    }

    /**
     * Fields to be shown on lists
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', 'integer', array('label' => 'admin.param.id'))
            ->addIdentifier('param', 'string', array('label' => 'admin.param.name', 'editable' => false))
            ->addIdentifier('value', 'string', array('label' => 'admin.param.value', 'editable' => true))
        ;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list'));
    }

    /**
     * Log create before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\AppBundle\Entity\Param $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
        $this->logParam($object);
        parent::prePersist($object);
    }

    /**
     * Log editing before inserting to database
     * @inheritdoc
     *
     * @param \Food\AppBundle\Entity\Param $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        $this->logParam($object);
        parent::preUpdate($object);
    }

    /**
     * @param \Food\AppBundle\Entity\Param $object
     * @return void
     */
    private function logParam($object)
    {
        $miscUtils = $this->getContainer()->get('food.app.utils.misc');
        $dm = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();
        $uow = $dm->getUnitOfWork();
        $original = $uow->getOriginalEntityData($object);
        $miscUtils->logParamChange($object, $original['value']);
    }
}
