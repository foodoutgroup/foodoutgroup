<?php
namespace Food\OrderBundle\Admin;

@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', 300);

use Food\AppBundle\Admin\Admin as FoodAdmin;
use Food\OrderBundle\Entity\Coupon;
use Food\OrderBundle\Entity\CouponRange;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
class CouponRangeAdmin extends FoodAdmin
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
        $formMapper
            ->add('name', 'text', array('label' => 'admin.coupon.name', 'required' => true))
            ->add('prefix', 'text', array('label' => 'admin.coupon.prefix', 'required' => true))
            ->add('suffix', 'text', array('label' => 'admin.coupon.suffix', 'required' => false))
            ->add('couponsQty', 'number', array('label' => 'admin.coupon.coupons_qty', 'required' => true))
            ->add('cartAmount', 'number', array('label' => 'admin.coupon.cart_amount', 'required' => false))
            ->add('discountSum', 'number', array('label' => 'admin.coupon.discount_sum', 'required' => false))
            ->add('discount', 'number', array('label' => 'admin.coupon.discount', 'required' => false))
            ->add('fullOrderCovers', 'checkbox', array('label' => 'admin.coupon.full_order_cover', 'required' => false))
            ->add('freeDelivery', null, array('label' => 'admin.coupon.free_delivery', 'required' => false))
            ->add('places', null, array('label' => 'admin.coupon.place', 'required' => false))
            ->add('noSelfDelivery','checkbox', array('required' => false))
            ->add('b2b', 'choice', array('choices' => array(
                Coupon::B2B_BOTH => 'BOTH',
                Coupon::B2B_YES => 'ONLY B2B',
                Coupon::B2B_NO => 'NOT B2B'
            ), 'required' => true))
            ->add('singleUse', 'checkbox', array('label' => 'admin.coupon.single_use', 'required' => false))
            ->add('singleUsePerPerson', 'checkbox', array('label' => 'admin.coupon.single_use_per_person', 'required' => false))
            ->add('onlinePaymentsOnly', 'checkbox', array('label' => 'admin.coupon.online_payments_only', 'required' => false))
            ->add('enableValidateDate', 'checkbox', array('required' => false))
            ->add('validFrom', 'datetime', array('label' => 'admin.coupon.valid_from', 'required' => false))
            ->add('validTo', 'datetime', array('label' => 'admin.coupon.valid_to', 'required' => false))
            ->add('validHourlyFrom', 'time', array('required' => false))
            ->add('validHourlyTo', 'time', array('required' => false))
            ->add('ignoreCartPrice', 'checkbox', [
                'required' => false,
                'label' => 'Ignore Minimal Price'
            ])
            ->add('includeDelivery', 'checkbox', [
                'required' => false,
            ])
            ->add('active', 'checkbox', array('label' => 'admin.coupon.active', 'required' => false))

        ;
        if ($this->getContainer()->getParameter('country') == "LT"
            || $this->getContainer()->getParameter('country')== 'LV') {
            $formMapper->add('onlyNav', 'checkbox', array('label' => 'admin.coupon.only_nav', 'required' => false));
        }
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
            ->add('name', null, array('label' => 'admin.coupon.name'))
            ->add('active', null, array('label' => 'admin.coupon.active'))
            ->add('singleUse', null, array('label' => 'admin.coupon.single_use'))
            ->add('places', null, array('label' => 'admin.coupon.place'))
            ->add('freeDelivery', null, array('label' => 'admin.coupon.free_delivery'))
        ;
        if ($this->getContainer()->getParameter('country') == "LT"
            || $this->getContainer()->getParameter('country')== 'LV') {
            $datagridMapper->add('onlyNav', null, array('label' => 'admin.coupon.only_nav'));
        }
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
            ->addIdentifier('id', 'integer', array('label' => 'admin.coupon.id'))
            ->addIdentifier('name', 'string', array('label' => 'admin.coupon.name', 'editable' => false))
            ->add('couponsQty', null, array('editable' => false))
            ->add('discountSum', null, array('editable' => false))
            ->add('discount', null, array('label' => 'admin.coupon.discount', 'editable' => false))
            ->add('freeDelivery', null, array('label' => 'admin.coupon.free_delivery', 'editable' => false))
            ->add('places', null, array('label' => 'admin.coupon.place', 'editable' => false))
            ->add('active', null, array('label' => 'admin.coupon.active', 'editable' => false))
            ->add('singleUse', null, array('label' => 'admin.coupon.single_use', 'editable' => false))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'delete' => array(),
                    'downloadCoupons' => array(
                        'template' => 'FoodOrderBundle:CRUD:list__action_downloadCoupons.html.twig'
                    ),
                ),
                'label' => 'admin.actions'
            ))
        ;
        if ($this->getContainer()->getParameter('country') == "LT"
            || $this->getContainer()->getParameter('country')== 'LV') {
            $listMapper->add('onlyNav', null, array('label' => 'admin.coupon.only_nav', 'editable' => false));
        }
    }
    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', null, array('label' => 'admin.coupon.id', 'editable' => false))
            ->add('name', null, array('label' => 'admin.coupon.name', 'editable' => false))
            ->add('prefix', null, array('label' => 'admin.coupon.prefix', 'editable' => false))
            ->add('suffix', null, array('label' => 'admin.coupon.suffix', 'editable' => false))
            ->add('couponsQty', null, array('label' => 'admin.coupon.coupons_qty', 'editable' => false))
            ->add('cartAmount', null, array('label' => 'admin.coupon.cart_amount', 'editable' => false))
            ->add('discountSum', null, array('label' => 'admin.coupon.discount_sum', 'editable' => false))
            ->add('discount', null, array('label' => 'admin.coupon.discount', 'editable' => false))
            ->add('fullOrderCovers', null, array('label' => 'admin.coupon.full_order_cover', 'editable' => false))
            ->add('freeDelivery', null, array('label' => 'admin.coupon.free_delivery', 'editable' => false))
            ->add('places', null, array('label' => 'admin.coupon.place', 'editable' => false))
            ->add('noSelfDelivery', null, array('editable' => false))
            ->add('singleUse', null, array('label' => 'admin.coupon.single_use', 'editable' => false))
            ->add('enableValidateDate', null, array('editable' => false))
            ->add('validFrom', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.coupon.valid_from'))
            ->add('validTo', 'datetime', array('format' => 'Y-m-d H:i:s', 'label' => 'admin.coupon.valid_to'))
            ->add('active', null, array('label' => 'admin.coupon.active', 'editable' => false))
        ;
    }
    /**
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     * @inheritdoc
     *
     * @codeCoverageIgnore
     */
    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show', 'create', 'delete'))
            ->add('downloadCoupons', $this->getRouterIdParameter().'/downloadCoupons');
    }
    /**
     * @param \Food\OrderBundle\Entity\CouponRange $object
     * @return void
     */
    public function prePersist($object)
    {
        $object = $this->generateCouponsRange($object);
        parent::prePersist($object);
    }
    /**
     * @param \Food\OrderBundle\Entity\CouponRange $object
     * @return void
     */
    public function preUpdate($object)
    {
        $object = $this->generateCouponsRange($object);
        parent::preUpdate($object);
    }
    /**
     * @param \Food\OrderBundle\Entity\CouponRange $object
     * @return void
     */
    public function delete($object)
    {
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();
        $em = $this->getContainer()->get('doctrine')->getManager();
        if (count($object->getCoupons()) > 0) {
            foreach ($object->getCoupons() as $coupon) {
                $coupon->setDeletedAt(new \DateTime('NOW'));
                $coupon->setDeletedBy($user);
                $em->persist($coupon);
                $em->flush();
            }
            $this->getConfigurationPool()->getContainer()->get('session')->getFlashBag()->add(
                'sonata_flash_success', 'All related coupons was deleted.'
            );
        }
        parent::delete($object);
    }
    /**
     * @param $obj CouponRange
     * @return mixed
     * @codeCoverageIgnore
     */
    private function generateCouponsRange($obj)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $coupons_qty = $obj->getCouponsQty();
        if (!empty($coupons_qty) && $coupons_qty > 0) {
            for ($i = 1; $i <= $coupons_qty; $i++) {
                $part = "";
                if ($i < 1000) {
                    $part = "0";
                }
                if ($i < 100) {
                    $part="00";
                }
                if ($i < 10) {
                    $part="000";
                }
                $someR = $i % 65;
                $part2 = "";
                if ($someR < 10) {
                    $part2 = "0";
                }
                $coupon = new Coupon();
                $coupon->setCouponRange($obj);
                $coupon->setActive($obj->getActive());
                $coupon->setDiscount($obj->getDiscount());
                $coupon->setEditedAt($obj->getEditedAt());
                $coupon->setEditedBy($obj->getEditedBy());
                $coupon->setCreatedAt(new \DateTime("now"));
                $coupon->setCreatedBy($obj->getCreatedBy());
                $coupon->setDeletedAt($obj->getDeletedAt());
                $coupon->setDeletedBy($obj->getDeletedBy());
                $coupon->setSingleUse($obj->getSingleUse());
                $coupon->setSingleUsePerPerson($obj->getSingleUsePerPerson());
                $coupon->setOnlinePaymentsOnly($obj->getOnlinePaymentsOnly());
                $coupon->setValidFrom($obj->getValidFrom());
                $coupon->setValidTo($obj->getValidTo());
                $coupon->setValidHourlyFrom($obj->getValidHourlyFrom());
                $coupon->setValidHourlyTo($obj->getValidHourlyTo());
                $coupon->setB2b($obj->getB2b());
                $coupon->setDiscountSum($obj->getDiscountSum());
                $coupon->setFreeDelivery($obj->getFreeDelivery());
                $coupon->setNoSelfDelivery($obj->getNoSelfDelivery());
                $coupon->setFullOrderCovers($obj->getFullOrderCovers());
                $coupon->setEnableValidateDate($obj->getEnableValidateDate());
                $coupon->setIgnoreCartPrice($obj->getIgnoreCartPrice());
                $coupon->setIncludeDelivery($obj->getIncludeDelivery());
                $coupon->setCode($obj->getPrefix() . $part . $i . $part2 . $someR . $obj->getSuffix());
                $coupon->setName($obj->getName(). ' - ' . date('Y-m-d'));
                if ($this->getContainer()->getParameter('country') == "LT"
                    || $this->getContainer()->getParameter('country')== 'LV') {
                    $coupon->setOnlyNav($obj->getOnlyNav());
                }
                if (count($obj->getPlaces()) > 0) {
                    foreach($obj->getPlaces() as $place) {
                        $coupon->addPlace($place);
                    }
                }
                $em->persist($coupon);
                if ($i % 200 == 0) {
                    $em->flush();
                }
            }
            if ($i % 200 != 0) {
                $em->flush();
            }
        }
        return $obj;
    }
}
