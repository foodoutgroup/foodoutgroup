<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Entity\CallLog;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class CallLogAdmin extends FoodAdmin
{
    public function configureListFields(ListMapper $list)
    {
        $list
            ->add('id')
            ->add('callDate', null, ['label' => $this->trans('admin.general.date')])
            ->add('number', null, ['label' => $this->trans('admin.general.phone_number')])
            ->add('type', null, ['label' => $this->trans('admin.general.type')])
            ->add('user', null, ['label' => $this->trans('admin.general.user')])
            ->add('order_id', null, ['label' => $this->trans('admin.general.order'), 'admin_code' => 'sonata.admin.order'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add(
                'type',
                'doctrine_orm_string',
                array('label' => $this->trans('admin.general.type')),
                'choice',
                array(
                    'choices' => array(
                        CallLog::TYPE_CLIENT,
                        CallLog::TYPE_RESTAURANT,
                        CallLog::TYPE_DRIVER,
                    )
                )
            )
            ->add('user.email', null, array('label' => 'admin.users.email'))
            ->add('callDate', 'doctrine_orm_date', array('label' => $this->trans('admin.general.date')), null, array('widget' => 'single_text', 'required' => false,  'attr' => array('class' => 'datepicker2')))
        ;
    }
}