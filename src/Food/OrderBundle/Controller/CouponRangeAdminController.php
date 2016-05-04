<?php
namespace Food\OrderBundle\Controller;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exporter\Source\ArraySourceIterator;
use Exporter\Handler;
use Exporter\Writer\XlsWriter;
class CouponRangeAdminController extends Controller
{
    /**
     * @param null $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function downloadCouponsAction($id = null)
    {
        $filename = sprintf(
            'export_%s_%s.%s',
            strtolower(substr($this->admin->getClass(), strripos($this->admin->getClass(), '\\') + 1)),
            date('Y_m_d_His', strtotime('now')), 'xls'
        );
        $qry = "SELECT c.* FROM `coupons` c WHERE c.deleted_at IS NULL AND c.coupon_range = '" . intval($id) . "'";
        $data = $this->get('database_connection')->fetchAll($qry);
        if (count($data)) {
            $source = new ArraySourceIterator($data);
            $writer = new XlsWriter('php://output');
            $callback = function() use ($source, $writer) {
                Handler::create($source, $writer)->export();
            };
            return new StreamedResponse($callback, 200, array(
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => sprintf('attachment; filename=%s', $filename)
            ));
        }
        $this->get('session')->getFlashBag()->add(
            'sonata_flash_error',
            $this->get('translator')->trans('admin.coupon.coupons_not_found', array(), 'SonataAdminBundle')
        );
        return $this->redirect($this->generateUrl('admin_food_order_couponrange_list'));
    }
}
