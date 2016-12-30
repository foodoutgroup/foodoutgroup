<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
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
            ->add('orderId', null, ['label' => $this->trans('admin.general.order')])
        ;
    }
}