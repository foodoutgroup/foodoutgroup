<?php

namespace Food\OrderBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exporter\Source\DoctrineDBALConnectionSourceIterator;
use Exporter\Handler;
use Exporter\Writer\XlsWriter;
use Exporter\Writer\XmlWriter;
use Exporter\Writer\JsonWriter;
use Exporter\Writer\CsvWriter;

class OrderAdminController extends Controller
{
    /**
     * @param null $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function sendInvoiceAction($id = null)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderById($id);

        // Leidziame siusti bet kam
//        if (!$order->getPlace()->getSendInvoice()) {
//            throw new \Exception('Place has disabled invoices - cant send invoice to user');
//        }

        $orderSfSeries = $order->getSfSeries();
        if (empty($orderSfSeries)) {
            $orderService->setInvoiceDataForOrder();
        }

        // Double check as we had an impossible error by not adding invoice for generation
        $orderSfSeries = $order->getSfSeries();
        if (empty($orderSfSeries)) {
            $this->container->get('logger')->error('sendInvoiceAction in OrderAdmin did not set SF series..');
        }

        $this->get('food.invoice')->addInvoiceToSend($order, false, true);

        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('admin.order.invoice_added_for_send', array(), 'SonataAdminBundle')
        );

        return $this->redirect(
            $this->generateUrl('admin_food_order_order_list')
        );
    }

    /**
     * @param null $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function downloadInvoiceAction($id = null)
    {
        $orderService = $this->get('food.order');
        $invoiceService = $this->get('food.invoice');
        $order = $orderService->getOrderById($id);

        $fileName = $invoiceService->getInvoiceFilename($order);
        $file = 'https://s3-eu-west-1.amazonaws.com/foodout-invoice/pdf/'.$fileName;

        $content = file_get_contents($file);

        $response = new Response();

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$fileName);

        $response->setContent($content);

        return $response;
    }


    /**
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportAction(Request $request)
    {
        if (false === $this->admin->isGranted('EXPORT')) {
            throw new AccessDeniedException();
        }

        $source = $this->getDataSourceIterator($request);
        $format = $request->get('format');

        $allowedExportFormats = (array) $this->admin->getExportFormats();

        if (!in_array($format, $allowedExportFormats) ) {
            throw new \RuntimeException(sprintf('Export in format `%s` is not allowed for class: `%s`. Allowed formats are: `%s`', $format, $this->admin->getClass(), implode(', ', $allowedExportFormats)));
        }

        $filename = sprintf('export_%s_%s.%s',
            strtolower(substr($this->admin->getClass(), strripos($this->admin->getClass(), '\\') + 1)),
            date('Y_m_d_H_i_s', strtotime('now')),
            $format
        );

        switch ($format) {
            case 'xls':
                $writer      = new XlsWriter('php://output');
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'xml':
                $writer      = new XmlWriter('php://output');
                $contentType = 'text/xml';
                break;
            case 'json':
                $writer      = new JsonWriter('php://output');
                $contentType = 'application/json';
                break;
            case 'csv':
                $writer      = new CsvWriter('php://output', ',', '"', "", true, true);
                $contentType = 'text/csv';
                break;
            default:
                throw new \RuntimeException('Invalid format');
        }

        $callback = function() use ($source, $writer) {
            Handler::create($source, $writer)->export();
        };

        return new StreamedResponse($callback, 200, array(
            'Content-Type'        => $contentType,
            'Content-Disposition' => sprintf('attachment; filename=%s', $filename)
        ));
    }

    /**
     * @param Request $request
     * @return DoctrineDBALConnectionSourceIterator
     */
    public function getDataSourceIterator(Request $request)
    {
        $where = "";
        $filters = $request->get('filters');
        $params = $request->get('filter');
        if($filters != 'reset' && !empty($params)) {
            $prepare_val = function($val) {
                if (ctype_digit($val)) {
                    $val = (int) $val;
                } else {
                    $val = "'" . str_replace("'", "", $val) . "'";
                }
                return $val;
            };

            foreach ($params as $key => $value) {
                if (substr($key, 0, 1) !== '_') {
                    switch ($key) {
                        case 'order_date':
                            if ($value && !empty($value['value']) && is_array($value['value'])) {
                                $range = "";
                                $b = 0;
                                foreach ($value['value'] as $k => $v) {
                                    if (!empty($v)){
                                        $range .= ($b==0 ? '' : ' AND ') . "'" . str_replace("'", "", $v) . "'";
                                        $b++;
                                    }
                                }
                                if (!empty($range) && !empty($value['value']['start']) && !empty($value['value']['end'])) {
                                    $where .= " AND (o.order_date BETWEEN " . $range . ')';
                                } elseif (!empty($range) && !empty($value['value']['start']) && empty($value['value']['end'])) {
                                    $where .= " AND (o.order_date >= '" . str_replace("'", "", $value['value']['start']) . "')";
                                } elseif (!empty($range) && !empty($value['value']['end']) && empty($value['value']['start'])) {
                                    $where .= " AND (o.order_date <= '" . str_replace("'", "", $value['value']['end']) . "')";
                                }
                            }
                            break;
                        case 'city':
                            if (!empty($value['value'])) {
                                $where .= " AND o.place_point_city LIKE " . $prepare_val("%" . $value['value'] . "%");
                            }
                            break;
                        case 'address':
                            if (!empty($value['value'])) {
                                $where .= " AND o.place_point_address LIKE " . $prepare_val("%" . $value['value'] . "%");
                            }
                            break;
                        case 'phone':
                            if (!empty($value['value'])) {
                                $where .= " AND oe.phone LIKE " . $prepare_val("%" . str_replace("+", "", $value['value']) . "%");
                            }
                            break;
                        case 'paymentStatus':
                            if (!empty($value['value'])) {
                                $where .= " AND o.payment_status = " . $prepare_val($value['value']);
                            }
                            break;
                        case 'mobile':
                            if (!empty($value['value']) && $value['value'] != 2) {
                                $where .= " AND o.mobile = " . $prepare_val($value['value'] == 2 ? null : $value['value']);
                            }
                            break;
                        case 'userIp':
                            if (!empty($value['value'])) {
                                $where .= " AND o.user_ip = " . $prepare_val($value['value']);
                            }
                            break;
                        case 'couponCode':
                            if (!empty($value['value'])) {
                                $where .= " AND o.coupon_code = " . $prepare_val($value['value']);
                            }
                            break;
                        case 'navDeliveryOrder':
                            if (!empty($value['value'])) {
                                $where .= " AND o.nav_delivery_order = " . $prepare_val($value['value']);
                            }
                            break;
                        case 'sfNumber':
                            if (!empty($value['value'])) {
                                $where .= " AND o.sf_number = " . $prepare_val($value['value']);
                            }
                            break;
                        case 'orderFromNav':
                            if (!empty($value['value'])) {
                                $where .= " AND o.order_from_nav = " . $prepare_val($value['value']);
                            }
                            break;
                        case 'total':
                            if (!empty($value['value'])) {
                                $where .= " AND o.total = " . $prepare_val(str_replace(",", ".", $value['value']));
                            }
                            break;
                        default:
                            if (is_array($value)) {
                                if (!empty($value['value'])) {
                                    $where .= " AND o." . $key . " = " . $prepare_val($value['value']);
                                }
                            } else {
                                if (!empty($value)) {
                                    $where .= " AND o." . $key . " = " . $prepare_val($value);
                                }
                            }
                            break;
                    }
                }
            }
        }

        $conn = $this->get('database_connection');
        $qry = "SELECT o.*, oe.*, d.extId as driver_id
                FROM orders o
                LEFT JOIN order_extra oe ON o.id = oe.order_id
                INNER JOIN drivers d ON o.driver_id = d.id
                WHERE 1 = 1 " . $where;

        return new DoctrineDBALConnectionSourceIterator($conn, $qry);
    }
}
