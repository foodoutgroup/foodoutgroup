<?php
namespace Food\BlogBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class BlogCategoryAdmin extends FoodAdmin
{

    function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('title', 'string', array('label' => 'admin.static.title'))
            ->add('language', 'choice', array('label' => 'admin.language', 'required' => false, 'choices' => $this->getContainer()->get('food.app.utils.language')->getAll()))
            ->add('order_no', 'integer', array('label' => 'admin.static.order_no_short', 'editable' => true))
            ->add('active', null, array('label' => 'admin.static.active', 'editable' => true))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ));
    }

    function configureFormFields(FormMapper $form)
    {
        $form
            ->add('title', null, array('label' => 'admin.static.title', 'required' => false))
            ->add('language', 'choice', array('label' => 'admin.language', 'required' => false, 'choices' => $this->getContainer()->get('food.app.utils.language')->getAll()))
            ->add('seo_title', null, array('label' => 'admin.seo_title', 'required' => false))
            ->add('seo_description', null, array('label' => 'admin.seo_description', 'required' => false))
            ->add('order_no', 'integer', array('label' => 'admin.static.order_no'))
            ->add('active', 'checkbox', array('label' => 'admin.static.active', 'required' => false));
    }
}
