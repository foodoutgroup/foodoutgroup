<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Food\AppBundle\Validator\Constraints\Slug;
use \Food\AppBundle\Entity\Slug as SlugEntity;

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

        $hookDescription = $this->getContainer()->get('templating')->render('@FoodApp/Admin/Custom/static_page_hook_description.html.twig', []);

        $formMapper->add(
            'translations',
            'a2lix_translations_gedmo',
            array(
                'translatable_class' => 'Food\AppBundle\Entity\StaticContent',
                'fields' => array(
                    'title' => array('label' => 'admin.static.title','attr'=>['data-slugify'=>'title']),
                    'content' => array('label' => 'admin.static.content', 'attr' => ['help' => $hookDescription, 'class' => 'ckeditor_custom']),
                    'seo_title' => array('label' => 'admin.static.seo_title', 'required' => false,),
                    'seo_description' => array('label' => 'admin.static.seo_description', 'attr' => ['rows' => 4, 'style' => 'width:610px !important;'], 'required' => false),
                    'slug' => [
                        'constraints' => new Slug(SlugEntity::TYPE_PAGE, $formMapper),
                        'attr'=>['data-slugify'=>'title']

                    ]
                )
            ))
            ->add('order', 'integer', array('label' => 'admin.static.order_no'))
            ->add('active', 'checkbox', array('label' => 'admin.static.active', 'required' => false))
            ->add('visible', 'checkbox', array('label' => 'admin.static.visible', 'required' => false));
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
            ->add('active', null, array('label' => 'admin.static.active'))
            ->add('visible', null, array('label' => 'admin.static.visible'));
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
            ->add('active', null, array('label' => 'admin.static.active', 'editable' => true))
            ->add('visible', null, array('label' => 'admin.static.visible', 'editable' => true))
            ->add('editedAt', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.edited_at'))
//            ->add('editedBy', null, array('label' => 'admin.edited_by'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ));
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'show', 'create', 'delete'));
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
            ->add('content', null, array('label' => 'admin.static.content'));
    }

    /**
     * @param \Food\AppBundle\Entity\Static $object
     *
     * @return mixed|void
     * @codeCoverageIgnore
     */
    public function postPersist($object)
    {
        $this->slug($object);

        parent::postPersist($object);
    }

    /**
     * @param \Food\AppBundle\Entity\Static $object
     *
     * @return mixed|void
     */
    public function postUpdate($object)
    {
        $this->slug($object);

        parent::postUpdate($object);
    }

    private function slug($object)
    {
        $slugService = $this->getContainer()->get('slug');
        $slugService->generate($object, 'slug', SlugEntity::TYPE_PAGE);
    }


}
