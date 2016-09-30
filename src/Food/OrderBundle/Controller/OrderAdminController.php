<?php

namespace Food\OrderBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exporter\Source\ArraySourceIterator;
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
            $this->get('translator')->trans('admin.order.invoice_added_for_send', [], 'SonataAdminBundle')
        )
        ;

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
        $file = 'https://s3-eu-west-1.amazonaws.com/foodout-invoice/pdf/' . $fileName;

        $content = file_get_contents($file);

        $response = new Response();

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName);

        $response->setContent($content);

        return $response;
    }


    /**
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportAction(Request $request)
    {
        if (false === $this->admin->isGranted('EXPORT')) {
            throw new AccessDeniedException();
        }

        $source = $this->getDataSourceIterator($request);
        $format = $request->get('format');

        $allowedExportFormats = (array)$this->admin->getExportFormats();

        if (!in_array($format, $allowedExportFormats)) {
            throw new \RuntimeException(sprintf('Export in format `%s` is not allowed for class: `%s`. Allowed formats are: `%s`', $format, $this->admin->getClass(), implode(', ', $allowedExportFormats)));
        }

        $filename = sprintf('export_%s_%s.%s',
            strtolower(substr($this->admin->getClass(), strripos($this->admin->getClass(), '\\') + 1)),
            date('Y_m_d_H_i_s', strtotime('now')),
            $format
        );

        switch ($format) {
            case 'xls':
                $writer = new XlsWriter('php://output');
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'xml':
                $writer = new XmlWriter('php://output');
                $contentType = 'text/xml';
                break;
            case 'json':
                $writer = new JsonWriter('php://output');
                $contentType = 'application/json';
                break;
            case 'csv':
                $writer = new CsvWriter('php://output', ',', '"', "", true, true);
                $contentType = 'text/csv';
                break;
            default:
                throw new \RuntimeException('Invalid format');
        }

        $callback = function () use ($source, $writer) {
            Handler::create($source, $writer)->export();
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => sprintf('attachment; filename=%s', $filename)
        ]);
    }

    /**
     * @param Request $request
     *
     * @return DoctrineDBALConnectionSourceIterator
     */
    public function getDataSourceIterator(Request $request)
    {
        @ini_set('memory_limit', 1024 * 1024 * 1024);
        $where = "";
        $filters = $request->get('filters');
        $params = $request->get('filter');
        if ($filters != 'reset' && !empty($params)) {
            $prepare_val = function ($val) {
                if (is_array($val)) {
                    return $val[0];
                }
                if (ctype_digit($val)) {
                    $val = (int)$val;
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
                                    if (!empty($v)) {
                                        $range .= ($b == 0 ? '' : ' AND ') . "'" . str_replace("'", "", $v) . "'";
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
                        case 'place_name':
                            if (!empty($value['value'])) {
                                $where .= " AND o.place_name LIKE " . $prepare_val("%" . $value['value'] . "%");
                            }
                            break;
                        case 'address':
                            if (!empty($value['value'])) {
                                $where .= " AND ua.address LIKE " . $prepare_val("%" . $value['value'] . "%");
                            }
                            break;
                        case 'phone':
                            if (!empty($value['value'])) {
                                $where .= " AND oe.phone LIKE " . $prepare_val("%" . str_replace("+", "", $value['value']) . "%");
                            }
                            break;
                        case 'email':
                            if (!empty($value['value'])) {
                                $where .= " AND oe.email LIKE " . $prepare_val("%" . $value['value'] . "%");
                            }
                            break;
                        case 'paymentStatus':
                            if (!empty($value['value'])) {
                                $where .= " AND o.payment_status = " . $prepare_val($value['value']);
                            }
                            break;
                        case 'deliveryType':
                            if (!empty($value['value'])) {
                                $where .= " AND o.delivery_type = " . $prepare_val($value['value']);
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
                        case 'user__isBussinesClient':

                            if (isset($value['value'])) {
                                $value = (int)$value['value'];
                                if ($value) {
                                    $where .= " AND cc.is_bussines_client = 1";
                                }
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


        $qry = "SELECT 
                  count(o.id)
                FROM orders o
                LEFT JOIN user_address ua ON o.address_id = ua.id
                LEFT JOIN order_extra oe ON o.id = oe.order_id
                LEFT JOIN place_point pp ON o.point_id = pp.id /* pakeitimas */
                LEFT JOIN fos_user u ON u.id = o.dispatcher_id AND o.dispatcher_id IS NOT NULL
                LEFT JOIN fos_user cc ON cc.id = o.user_id
                LEFT JOIN drivers d ON o.driver_id = d.id
                WHERE 1 = 1 $where";;
        $total = $this->get('database_connection')->fetchColumn($qry);

        $data = [];

        for ($i = 0; $i < $total; $i += 1000) {
            $qry = "SELECT 
                      o.id AS order_id, o.order_date, o.order_status, o.comment, o.place_comment, o.order_hash, 
                      o.payment_method, o.payment_status, o.submitted_for_payment, o.last_updated, o.last_payment_error,
                      o.delivery_type, o.preorder,
                      o.mobile, o.nav_delivery_order, o.order_from_nav, o.nav_driver_code, 
                      o.place_id, o.point_id, o.place_name, o.place_point_address, o.place_point_city, o.place_point_self_delivery, 
                      o.driver_id, d.extId as driver_ext_id, d.type AS driver_type,
                      o.total, o.vat, o.coupon_code, o.discount_size, o.discount_sum, o.delivery_price, o.sf_series, o.sf_number,
                      u.firstname AS dispatcher_name, 
                      oe.firstname, oe.lastname, oe.phone, oe.email, oe.cancel_reason, oe.cancel_reason_comment, oe.change_reason,
                      o.user_ip, o.is_corporate_client, o.company, o.company_name, o.company_code, o.vat_code, o.company_address,
                      o.newsletter_subscribe,
                      ua.city, ua.address, 
                      ua.lat, ua.lon, 
                      pp.lat as production_peaks_lat,
                      pp.lon as production_peaks_lon,
                      o.order_date as order_date_copy, o.accept_time, o.delivery_time, o.completed_time, 
                      o.is_delay, o.delay_duration, o.delay_reason, o.assign_late, o.during_zavalas 
                    FROM orders o
                    LEFT JOIN user_address ua ON o.address_id = ua.id
                    LEFT JOIN order_extra oe ON o.id = oe.order_id
                    LEFT JOIN place_point pp ON o.point_id = pp.id
                    LEFT JOIN fos_user u ON u.id = o.dispatcher_id
                    LEFT JOIN fos_user cc ON cc.id = o.user_id
                    LEFT JOIN drivers d ON o.driver_id = d.id
                    WHERE 1 = 1 $where ORDER BY o.id DESC LIMIT $i, 1000";

            $result = $this->get('database_connection')->fetchAll($qry);
            foreach ($result as $key => $row) {
                $log = $this->get('database_connection')->fetchAll('SELECT * FROM order_delivery_log WHERE order_id = ' . $row['order_id']);

                $row['driver_assign_time'] = null;
                $row['driver_pickup_time'] = null;
                $row['driver_finished_order'] = 'No';
                foreach ($log as $k => $v) {

                    switch ($v['event']) {
                        case "order_assigned":
                            $row['driver_assign_time'] = $v['event_date'];

                            break;
                        case "order_pickedup":
                            $row['driver_pickup_time'] = $v['event_date'];
                            break;
                        case "order_completed":
                            $row['driver_finished_order'] = "Yes";
                            break;
                    }

                }
                $row['diff_delivery_completed'] = null;
                if ($row['delivery_time'] && $row['completed_time']) {
                    $deliveryTime = new \DateTime($row['delivery_time']);
                    $completedTime = new \DateTime($row['completed_time']);
                    $diff = $deliveryTime->diff($completedTime);
                    $row['diff_delivery_completed'] = $diff->format('%R') . sprintf('%02d:%02d', $diff->d * 24 + $diff->h, $diff->i);
                }
                $row['approved_completed'] = null;
                if ($row['accept_time'] && $row['completed_time']) {
                    $acceptTime = new \DateTime($row['accept_time']);
                    $completedTime = new \DateTime($row['completed_time']);
                    $diff = $acceptTime->diff($completedTime);
                    $row['approved_completed'] = sprintf('%02d:%02d', $diff->d * 24 + $diff->h, $diff->i);
                }
                $row['started_completed'] = null;
                if ($row['order_date'] && $row['completed_time']) {
                    $orderDate = new \DateTime($row['order_date']);
                    $completedTime = new \DateTime($row['completed_time']);
                    $diff = $orderDate->diff($completedTime);
                    $row['started_completed'] = sprintf('%02d:%02d', $diff->d * 24 + $diff->h, $diff->i);
                }


                $data[] = $row;
                if (memory_get_usage() > 0.8 * ini_get('memory_limit')) {
                    break;
                }
            }
        }

        return new ArraySourceIterator($data);
    }
}
