<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class StaticContentAdmin extends FoodAdmin
{
    /**
     * Fields to be shown on create/edit forms
     *
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\AppBundle\Entity\StaticContent',
                'fields' => array(
                    'title' => array('label' => 'admin.static.title'),
                    'content' => array('label' => 'admin.static.content', 'attr' => array('class' => 'ckeditor_custom'), )
                )
            ))
            ->add('order', 'integer', array('label' => 'admin.static.order_no'));
        ;
    }

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
            ->add('title', null, array('label' => 'admin.static.title'))
            ->add('editedAt', null, array('label' => 'admin.edited_at'))
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
            ->addIdentifier('title', 'string', array('label' => 'admin.static.title'))
            ->add('order', 'integer', array('label' => 'admin.static.order_no_short', 'editable' => true))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
            ->add('editedBy', null, array('label' => 'admin.edited_by'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'show', 'create'));
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        $showMapper
            ->add('title', null, array('label' => 'admin.static.title'))
            ->add('content', null, array('label' => 'admin.static.content'))
        ;
    }

    /**
     * @param \Food\AppBundle\Entity\Static $object
     *
     * @return mixed|void
     * @codeCoverageIgnore
     */
    public function postPersist($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * @param \Food\AppBundle\Entity\Static $object
     *
     * @return mixed|void
     */
    public function postUpdate($object)
    {
        $this->fixSlugs($object);
    }

    /**
     * Lets fix da stufffff.... Slugs for Static :)
     *
     * @param \Food\AppBundle\Entity\StaticContent $object
     */
    private function fixSlugs($object)
    {
        $origName = $object->getTitle();
        $locales = $this->getContainer()->getParameter('available_locales');
        $textsForSlugs = array();
        // Neprognozuojamas veikimas.. ima content fielda, o ne title... nenurodom pagal ka generuoti..
        $translations = $object->getTranslations();

        foreach($translations->getValues() as $row) {
            if ($row->getField() == 'title') {
                $textsForSlugs[$row->getLocale()] = $row->getContent();
            }
        }
        foreach ($locales as $loc) {
            if (!isset($textsForSlugs[$loc])) {
                $textsForSlugs[$loc] = $origName;
            }
        }

        $languages = $this->getContainer()->get('food.app.utils.language')->getAll();
        $slugUtelyte = $this->getContainer()->get('food.dishes.utils.slug');
        foreach ($languages as $loc) {
            $slugUtelyte->generateForTexts($loc, $object->getId(), $textsForSlugs[$loc]);
        }
    }
}
