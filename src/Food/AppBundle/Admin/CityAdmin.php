<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Validator\Constraints\Slug;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use \Food\AppBundle\Entity\Slug as SlugEntity;
use Sonata\AdminBundle\Route\RouteCollection;

class CityAdmin extends FoodAdmin
{

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('clone', $this->getRouterIdParameter().'/clone');
    }

    function configureListFields(ListMapper $list)
    {
        $list
            ->add('title', null, array('label' => 'admin.cities.title', 'editable' => true))
            ->add('zavalasOn', null, array('label' => 'admin.cities.zavalas_on', 'editable' => true))
            ->add('zavalas_time', null, array('label' => 'admin.cities.zavalas_time', 'editable' => false))
            ->add('active', null, array('label' => 'admin.cities.active', 'editable' => true))
            ->add('pedestrian', null, array('label' => 'admin.cities.pedestrian', 'editable' => true))
            ->add('popUp', null, array('label' => 'admin.cities.popup', 'editable' => true))
            ->add('badge', null, array('label' => 'admin.cities.badge', 'editable' => true))
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

        $form->add('translations', 'a2lix_translations_gedmo', [
            'translatable_class' => 'Food\AppBundle\Entity\City',
                'cascade_validation'=>true,
                'fields' => [
                    'title' => [ ],
                    'meta_title' => ['required' => false],
                    'meta_description' => ['required' => false],
                    'slug' => [
                        'constraints' => new Slug(SlugEntity::TYPE_CITY, $form),
                        'attr'=>['data-slugify'=>'title']
                        ]
                ]
        ]);


        $form->add('code', 'text', array('required' => false))
            ->add('zavalas_on', 'checkbox', array('label' => 'admin.cities.zavalas_on', 'required' => false))
            ->add('zavalas_time', 'text', array('label' => 'admin.cities.zavalas_time', 'required' => true))
            ->add('position', 'text', array('required' => false))
            ->add('active', 'checkbox', array('required' => false))
            ->add('pedestrian', 'checkbox', array('label' => 'admin.cities.pedestrian', 'required' => false))
            ->add('pop_up', 'checkbox', array('label' => 'admin.cities.popup', 'required' => false))
            ->add('badge', 'checkbox', array('label' => 'admin.cities.badge', 'required' => false))
            ->add('pop_up_time_from','time', array('label' => 'admin.cities.popup_from','required' => false))
            ->add('pop_up_time_to','time', array('label' => 'admin.cities.popup_to','required' => false))

        ;

    }
//
//    /**
//     * @param City $object
//     */
//    public function preUpdate($object)
//    {
//        $this->logCity($object);
//        parent::preUpdate($object);
//    }
//
//    /**
//     * @param \Food\AppBundle\Entity\City $object
//     * @return void
//     */
//    private function logCity($object)
//    {
//        $miscUtils = $this->getContainer()->get('food.app.utils.misc');
//        $original = $this->getContainer()->get('doctrine.orm.entity_manager')
//            ->getRepository('FoodAppBundle:City')->find($object->getId());
//        $original = $this->getContainer()->get('doctrine.orm.entity_manager')
//            ->getUnitOfWork()->getOriginalEntityData($original);
//        $miscUtils->logCityChange($object, $original);
//    }

    function postPersist($object)
    {
        $this->slug($object);
        parent::postPersist($object);
    }

    function postUpdate($object)
    {
        $this->slug($object);
        parent::postUpdate($object);
    }

    /**
     * @param City $object
     */


    private function slug($object)
    {
        $slugService = $this->getContainer()->get('slug');
        $slugService->generate($object, 'slug', SlugEntity::TYPE_CITY);
    }

}
