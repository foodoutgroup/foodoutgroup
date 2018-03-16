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
            ->add('order_id.id', null, ['label' => $this->trans('admin.general.order')])
            ->add('order_id.place', null, ['label' => $this->trans('admin.general.place')])
            ->add('order_id.deliveryType', null, ['label' => $this->trans('admin.general.delivery_type')]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('order_id.id', null, array('label' => 'admin.users.order'))
            ->add('order_id.deliveryType', null, ['label' => $this->trans('admin.general.delivery_type')])
            ->add('order_id.place', null, ['label' => $this->trans('admin.general.place')])
            ->add('type', 'doctrine_orm_choice', ['label' => 'admin.users.type'], 'choice',
                [
                    'choices' => array(
                        CallLog::TYPE_CLIENT => CallLog::TYPE_CLIENT,
                        CallLog::TYPE_RESTAURANT => CallLog::TYPE_RESTAURANT,
                        CallLog::TYPE_DRIVER => CallLog::TYPE_DRIVER,
                    )
                ]
            )
            ->add('user.email', null, array('label' => 'admin.users.email'))
            ->add('callDate', 'doctrine_orm_date_range', array('label' => $this->trans('admin.general.date')), null, array('widget' => 'single_text', 'required' => false, 'attr' => array('class' => 'datepicker2')));
    }

    public function getExportFields()
    {
        $exportFields = [];
        $exportFields[] = 'id';
        $exportFields[] = 'order_id.id';
        $exportFields[] = 'user.id';
        $exportFields[] = 'callDate';
        $exportFields[] = 'number';
        $exportFields[] = 'type';
        $exportFields[] = 'order_id.place';
        $exportFields[] = 'order_id.place.id';





        return $exportFields;
    }

}