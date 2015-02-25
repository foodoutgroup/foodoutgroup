<?php
namespace Food\DishesBundle\Admin;

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * @package Food\DishesBundle\Admin
 */
class PlacePointFullAdmin extends FoodAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('address', 'text', array('label' => 'admin.point.address'))
            ->add('company_code', 'text', array('label' => 'admin.point.company_code'))
            ->add('city', 'text', array('label' => 'admin.point.city'))
            ->add('internal_code', null, array('label' => 'admin.place.internal_code', 'required' => false,))
            ->add('phone', 'text', array('label' => 'admin.point.phone', 'attr' => array('placeholder'=>'3706xxxxxxx')))
            ->add('alt_phone1', 'text', array('label' => 'admin.point.alt_phone', 'required' => false, 'attr' => array('placeholder'=>'370xxxxxxx')))
            ->add('alt_phone2', 'text', array('label' => 'admin.point.alt_phone', 'required' => false, 'attr' => array('placeholder'=>'370xxxxxxx')))
            ->add('email', 'text', array('label' => 'admin.point.email', 'required' => false))
            ->add('alt_email1', 'text', array('label' => 'admin.point.alt_email', 'required' => false))
            ->add('alt_email2', 'text', array('label' => 'admin.point.alt_email', 'required' => false))
            ->add('invoice_email', 'text', array('label' => 'admin.point.invoice_email', 'required' => false))
            //->add('coords', 'text', array('label' => 'admin.point.coords'))
            ->add('delivery_time', 'text', array('label' => 'admin.point.devtime'))
            ->add('lat', 'text', array('label' => 'admin.point.lat','attr'=>array('placeholder'=>'xx.xxxxx')))
            ->add('lon', 'text', array('label' => 'admin.point.lon','attr'=>array('placeholder'=>'xx.xxxxx')))
            ->add('public',null, array('label' => 'admin.point.public', 'required' => false))
            ->add('pickUp',null, array('label' => 'admin.point.pickup', 'required' => false))
            ->add('delivery', null, array('label' => 'admin.point.delivery', 'required' => false))
            ->add('active', null, array('label' => 'admin.point.active', 'required' => false))
            ->add('fast', null, array('label' => 'admin.point.fast', 'required' => false))
            ->add('allowCash', null, array('label' => 'admin.point.allow_cash', 'required' => false))
            ->add('allowCard', null, array('label' => 'admin.point.allow_card', 'required' => false))
            ->add('useExternalLogistics', null, array('label' => 'admin.point.use_external_logistics', 'required' => false))
            ->with('admin.point.work_time')
            ->add('wd1_start', 'text', array('label' => 'admin.point.wd1_start'))
            ->add('wd1_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd2_start', 'text', array('label' => 'admin.point.wd2_start'))
            ->add('wd2_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd3_start', 'text', array('label' => 'admin.point.wd3_start'))
            ->add('wd3_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd4_start', 'text', array('label' => 'admin.point.wd4_start'))
            ->add('wd4_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd5_start', 'text', array('label' => 'admin.point.wd5_start'))
            ->add('wd5_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd6_start', 'text', array('label' => 'admin.point.wd6_start'))
            ->add('wd6_end', 'text', array('label' => 'admin.point.wd_end'))
            ->add('wd7_start', 'text', array('label' => 'admin.point.wd7_start'))
            ->add('wd7_end', 'text', array('label' => 'admin.point.wd_end'))

            ->end()
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('city', null, array('label' => 'admin.point.city'));
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('place', null, array('label' => 'admin.point.place'))
            ->addIdentifier('address', 'string', array('label' => 'admin.point.address'))
            ->addIdentifier('city', 'string', array('label' => 'admin.point.city'))
            ->addIdentifier('active', 'boolean', array('label' => 'admin.point.active', 'editable' => true,))
            ->addIdentifier('fast', 'boolean', array('label' => 'admin.point.fast', 'editable' => true))
            ->add('allowCash', 'boolean', array('label' => 'admin.point.allow_cash', 'editable' => true))
            ->add('allowCard', 'boolean', array('label' => 'admin.point.allow_card', 'editable' => true))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
                'label' => 'admin.actions'
            ))
        ;
    }

    /**
     * @param \Food\DishesBundle\Entity\PlacePoint $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
        $this->_fixZones($object);
        $this->_fixExtendedWorkTime($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\PlacePoint $object
     */
    public function preUpdate($object)
    {
        $this->_fixZones($object);
        $this->_fixExtendedWorkTime($object);
    }

    /**
     * @param \Food\DishesBundle\Entity\PlacePoint $object
     */
    private function _fixExtendedWorkTime($object)
    {
        for($i = 1; $i<=7; $i++) {
            $wt = explode(":", $object->{'getWd'.$i.'End'}());
            $val = $object->{'getWd'.$i.'End'}();
            if (sizeof($wt) == 2) {
                if ($wt[0] <= 6) {
                    $wt[0] = $wt[0] + 24;
                    $object->{'setWd'.$i.'EndLong'}(implode("", $wt));
                } else {
                    $object->{'setWd'.$i.'EndLong'}(implode("", $wt));
                }
            } else {
                $object->{'setWd'.$i.'EndLong'}($val);
            }
        }
    }

    /**
     * @param \Food\DishesBundle\Entity\PlacePoint $object
     */
    private function _fixZones($object)
    {
        var_dump($_SERVER);
        foreach ($object->getZones() as $zone) {
            $zone->setPlacePoint($object);
            $cAt = $zone->getCreatedAt();
            if (empty($cAt)) {
                $zone->setCreatedAt(new \DateTime('now'));
            }
        }
    }
}