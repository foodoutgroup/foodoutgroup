<?php

namespace Food\DishesBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Form\FormMapper;

class DishOptionSizePriceAdmin extends FoodAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add(
            'unit',
            'entity',
            array(
                'class' => 'Food\DishesBundle\Entity\DishUnit',
                'group_by' => 'group',
                'multiple' => false,
                'query_builder' => function (EntityRepository $er) {
                    $place = null;
                    $req = $this->getRequest()->get($this->getRequest()->get('uniqid'));

                    if (!empty($req['place'])) {
                        $place = $req['place'];
                    } elseif (!$this->isAdmin()) {
                        $place = $this->getUser()->getPlace()->getId();
                    } else {
                        $place = $this->getSubject()->getDishOption()->getPlace()->getId();
                    }
                    return $er->createQueryBuilder('s')
                        ->where('s.place = ?1')
                        ->setParameter(1, $place);
                }
            )
        );

        $formMapper->add('price');
    }
}