<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class CityAdmin extends FoodAdmin
{

    function configureListFields(ListMapper $list)
    {
        $list
            ->add('title', null, array('label' => 'admin.cities.title', 'editable' => true))
            ->add('zavalas_on', 'boolean', array('label' => 'admin.cities.zavalas_on', 'editable' => true))
            ->add('zavalas_time', null, array('label' => 'admin.cities.zavalas_time', 'editable' => true))
            ->add('pedestrian', null, array('label' => 'admin.cities.pedestrian', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    function configureFormFields(FormMapper $form)
    {
        $form
            ->add('title', 'text', array('label' => 'admin.cities.title', 'required' => true))
            ->add('zavalas_on', 'checkbox', array('label' => 'admin.cities.zavalas_on', 'required' => false))
            ->add('zavalas_time', 'text', array('label' => 'admin.cities.zavalas_time', 'required' => false))
            ->add('pedestrian', 'checkbox', array('label' => 'admin.cities.pedestrian', 'required' => false))
        ;
    }

    /**
     * Log editing before inserting to database
     * @inheritdoc
     *
     * @param \Food\AppBundle\Entity\City $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        $this->logCity($object);
        parent::preUpdate($object);
    }

    /**
     * @param \Food\AppBundle\Entity\City $object
     * @return void
     */
    private function logCity($object)
    {
        $miscUtils = $this->getContainer()->get('food.app.utils.misc');
        $original = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('FoodAppBundle:City')->find($object->getId());
        $original = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getUnitOfWork()->getOriginalEntityData($original);
        $miscUtils->logCityChange($object, $original);
    }
}
