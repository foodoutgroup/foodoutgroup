<?php
namespace Food\AppBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\AppBundle\Entity\City;
use Food\AppBundle\Validator\Constraints\Slug;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use \Food\AppBundle\Entity\Slug as SlugEntity;

class CityAdmin extends FoodAdmin
{

    function configureListFields(ListMapper $list)
    {
        $list
            ->add('title', null, array('label' => 'admin.cities.title', 'editable' => true))
            ->add('zavalas_on', 'boolean', array('label' => 'admin.cities.zavalas_on', 'editable' => true))
            ->add('zavalas_time', null, array('label' => 'admin.cities.zavalas_time', 'editable' => true))
            ->add('active', null, array('label' => 'admin.cities.active', 'editable' => true))
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
                        'constraints' => new Slug('city', $form),
                        'attr'=>['data-slugify'=>'title']
                        ]
                ]
        ]);


        $form->add('zavalas_on', 'checkbox', array('label' => 'admin.cities.zavalas_on', 'required' => false))
            ->add('zavalas_time', 'text', array('label' => 'admin.cities.zavalas_time', 'required' => false))
            ->add('position', 'text', array('required' => false))
            ->add('active', 'checkbox', array('required' => false))
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
