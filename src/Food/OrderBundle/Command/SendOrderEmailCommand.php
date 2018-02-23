<?php

namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Food\OrderBundle\Service\OrderService;
use Food\OrderBundle\Entity\Order;

class SendOrderEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:email:send')
            ->setDescription('Send invoices to user and restaurant');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
//            $logger = $this->getContainer()->get('logger');
//            $invoiceService = $this->getContainer()->get('food.invoice');
//            $nav = $this->getContainer()->get('food.nav');
//
//            $forcedEmail = $input->getOption('force-email');
//            if (empty($forcedEmail)) {
//                $forcedEmail = null;
//            }
//            $dryRun = false;
//
//            $orders = $em->getRepository('FoodOrderBundle:InvoiceToSend')->getInvoiceToSend();

            $em = $this->getContainer()->get('doctrine')->getManager();


            $mails = $em->getRepository('FoodOrderBundle:OrderEmail')->getEmailsToSend();
            $orderRepository = $em->getRepository('FoodOrderBundle:Order');
            $placeService = $this->getContainer()->get('food.places');
            $ml = $this->getContainer()->get('food.mailer');

            if (!empty($mails)) {
                foreach ($mails as $mail) {
                    try {
                        $order = $orderRepository->find($mail->getOrderId());

                        $invoice = [];
                        foreach ($order->getDetails() as $ord) {

                            $optionCollection = $ord->getOptions();
                            $invoice[] = [
                                'itm_name' => $ord->getDishName(),
                                'itm_amount' => $ord->getQuantity(),
                                'itm_price' => $ord->getPrice(),
                                'itm_sum' => $ord->getPrice() * $ord->getQuantity(),
                            ];
                            if (count($optionCollection)) {

                                foreach ($optionCollection as $k => $opt) {

                                    $invoice[] = [
                                        'itm_name' => "  - " . $opt->getDishOptionName(),
                                        'itm_amount' => $ord->getQuantity(),
                                        'itm_price' => $opt->getPrice(),
                                        'itm_sum' => $opt->getPrice() * $ord->getQuantity(),
                                    ];
                                }

                            }
                        }


                        $variables = [
                            'place_name' => $order->getPlace()->getName(),
                            'place_address' => $order->getPlacePoint()->getAddress(),
                            'order_id' => $order->getId(),
                            'order_hash' => $order->getOrderHash(),
                            'user_address' => ($order->getDeliveryType() != OrderService::$deliveryPickup ? $order->getAddressId()->toString() : "--"),
                            'delivery_date' => $placeService->getDeliveryTime($order->getPlace()),
                            'total_sum' => $order->getTotal(),
                            'total_delivery' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? $order->getDeliveryPrice() : 0),
                            'total_card' => ($order->getDeliveryType() == OrderService::$deliveryDeliver ? ($order->getTotal() - $order->getDeliveryPrice()) : $order->getTotal()),
                            'city' => $order->getCityId() ? $order->getCityId()->getTitle() : $order->getPlacePoint()->getCityId()->getTitle(),
                            'food_review_url' => 'http://' . $this->getContainer()->getParameter('domain') . $this->getContainer()->get('slug')->getUrl($order->getPlace()->getId(), 'place') . '/#detailed-restaurant-review',
                            'delivery_time' => ($order->getDeliveryType() != OrderService::$deliveryPickup ? $placeService->getDeliveryTime($order->getPlace(), null, $order->getDeliveryType()) : $order->getPlace()->getPickupTime()),
                            'email' => $order->getUser()->getEmail(),
                            'invoice' => $invoice,
                            'beta_code' => '',
                            'phone' => $this->getPhoneForUserInform($order),
                            'delivery_time_format' => $this->getOrder()->getDeliveryTime()->format('H:i'),
                            'pre_delivery_time' => $this->getOrder()->getDeliveryTime()->format('m-d H:i')
                        ];


                        $mailResp = $ml->setVariables($variables)
                            ->setRecipient($order->getOrderExtra()->getEmail(), $order->getOrderExtra()->getEmail())
                            ->setId($mail->getTemplateId())
                            ->send();

                        if (isset($mailResp['errors'])) {
                            $this->getContainer()->get('logger')->error(
                                $mailResp['errors'][0]
                            );
                            $mail->setError($mailResp['errors'][0]);
                        } else {
                            $mail->setSent(true);
                        }

                        $mail->setSentAt(new \DateTime('now'));
                        $em->persist($mail);
                        $em->flush();


                    } catch (\Exception $e) {


                        throw $e;
                    }
                }
            }

        } catch (\Exception $e) {
            $output->writeln('Error sending order invoice');
            $output->writeln('Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPhoneForUserInform(Order $order)
    {
        $place = $order->getPlace();
        $deliveyType = $order->getDeliveryType();
        $trans = $this->getContainer()->get('translator');

        $phone = '';

        if ($deliveyType == 'pickup') {
            $phone = $order->getPlacePoint()->getPhoneNiceFormat();
        } else {
            if ($order->getPlacePointSelfDelivery()) {
                $phone = $order->getPlacePoint()->getPhoneNiceFormat();
            } else {
                $phone = $trans->trans('general.top_contact.phone');
            }
        }

        return $phone;

    }
}
