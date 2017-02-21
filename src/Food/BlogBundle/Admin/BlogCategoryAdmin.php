<?php
namespace Food\BlogBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\BlogBundle\Entity\BlogCategory;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class BlogCategoryAdmin extends FoodAdmin
{

    function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('title', 'string', array('label' => 'admin.static.title'))
            ->add('language', 'choice', array('label' => 'admin.language', 'choices' => $this->getLanguageChoice()))
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
            ->add('language', 'choice', array('label' => 'admin.language', 'required' => true, 'choices' => $this->getLanguageChoice()))
            ->add('seo_title', null, array('label' => 'admin.seo_title', 'required' => false))
            ->add('seo_description', null, array('label' => 'admin.seo_description', 'required' => false))
            ->add('order_no', 'integer', array('label' => 'admin.static.order_no'))
            ->add('active', 'checkbox', array('label' => 'admin.static.active', 'required' => false));
    }

    function getLanguageChoice()
    {
        $languages = [];
        foreach($this->getContainer()->get('food.app.utils.language')->getAll() as $language) {
            $languages[$language] = $language;
        }
        return $languages;
    }

    function postPersist($object)
    {
        $this->fixSlugs($object);
        parent::postPersist($object);
    }

    function postUpdate($object)
    {
        $this->fixSlugs($object);
        parent::postUpdate($object);
    }

    /**
     * @param BlogCategory $object
     */
    private function fixSlugs($object)
    {
        $slugService = $this->getContainer()->get('food.dishes.utils.slug');
        $slugService->generateForBlogCategory($object->getLanguage(), $object->getId(), $object->getTitle());
    }
}
