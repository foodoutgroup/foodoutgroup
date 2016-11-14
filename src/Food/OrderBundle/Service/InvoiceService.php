<?php

namespace Food\OrderBundle\Service;

use Doctrine\ORM\OptimisticLockException;
use Food\AppBundle\Entity\UnusedSfNumbers;
use Food\OrderBundle\Entity\InvoiceToSend;
use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class InvoiceService extends ContainerAware
{


    /**
     * @var S3Client
     */
    private $s3Client = null;

    /**
     * @return S3Client
     */
    public function getS3Client()
    {
        if (empty($this->s3Client)) {
            $config = array(
                'key'    => $this->container->getParameter('aws_key'),
                'secret' => $this->container->getParameter('aws_secret'),
                'region' => $this->container->getParameter('aws_region')
            );

            $this->s3Client = S3Client::factory($config);
        }

        return $this->s3Client;
    }

    /**
     * @param S3Client $client
     */
    public function setS3Client($client)
    {
        $this->s3Client = $client;
    }

    /**
     * @param Order $order
     * @param boolean $mustDoNavDelete
     * @param boolean $skipChecks
     * @throws \InvalidArgumentException
     */
    public function addInvoiceToSend($order, $mustDoNavDelete=false, $skipChecks=false)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('I need order to plan invoice generation');
        }

        // Invoice sending is not turned on
        if (!$order->getPlace()->getSendInvoice() && !$skipChecks) {
            return;
        }

        // No Invoice sending for this user
        if ($order->getUser()->getNoInvoice()) {
            return;
        }

        if (($order->getPlacePointSelfDelivery() || $order->getDeliveryType() == OrderService::$deliveryPickup) && !$skipChecks) {
            return;
        }

        $em = $this->container->get('doctrine')->getManager();

        $unsentItem = $em->getRepository('FoodOrderBundle:InvoiceToSend')->findBy(array(
            'order' => $order,
            'status' => 'unsent'
        ));

        // If there is a unsent Item already registered - dont do that again
        if (!empty($unsentItem) && count($unsentItem)>0) {
            return;
        }

        $invoiceTask = new InvoiceToSend();
        $invoiceTask->setOrder($order)
            ->setDateAdded(new \DateTime('now'))
            ->markUnsent();

        if ($mustDoNavDelete) {
            $invoiceTask->setDeleteFromNav(true);
        } else {
            $invoiceTask->setDeleteFromNav(false);
        }

        $em->persist($invoiceTask);
        $em->flush();
    }

    /**
     * @param Order $order
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getInvoiceFilename($order)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Cannot generate invoice filename without order');
        }

        return sprintf(
            '%s_%s_%s.pdf',
            'foodout',
            strtolower($this->container->getParameter('country')),
            $order->getSfSeries().$order->getSfNumber()
        );
    }

    /**
     * @return string
     */
    private function getInvoicePath()
    {
        return $this->container->get('kernel')->getRootDir() . '/../web/uploads/pdf/';
    }

    /**
     *
     * @param Order $order
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function generateUserInvoice($order)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Cannot generate invoice PDF without order');
        }

        $file = $this->getInvoiceFilename($order);
        $filename = $this->getInvoicePath().$file;

        $this->container->get('logger')->alert(
            sprintf(
                'Generating user invoice for Order #%d | with SF data: %s | Filename: %s | Filepath: %s',
                $order->getId(),
                $order->getSfLine(),
                $file,
                $filename
            )
        );

        // Generate new invoice file
        if (file_exists($filename)) {
            unlink($filename);
        }

        $this->container->get('knp_snappy.pdf')->generateFromHtml(
            $this->container->get('templating')->render(
                'FoodOrderBundle:Default:invoice.html.twig',
                array(
                    'order'  => $order,
                )
            ),
            $filename
        );
    }


    /**
     *
     * @param Order[] $orders
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function generateCorporateInvoice($orders)
    {
        if (empty($orders)) {
            throw new \InvalidArgumentException('Cannot generate invoice PDF without orders');
        }

        $file = $this->getInvoiceFilename($orders[0]);
        $filename = $this->getInvoicePath().$file;

        $user = $orders[0]->getUser();
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Cannot generate invoice without user');
        }

        $this->container->get('logger')->alert(
            sprintf(
                'Generating user invoice for Corportate Orders by user #%d | with SF data: %s | Filename: %s | Filepath: %s',
                $orders[0]->getId(),
                $orders[0]->getSfLine(),
                $file,
                $filename
            )
        );

        $orderByDivision = array();
        foreach ($orders as $order) {
            if ($user->getRequiredDivision()) {
                if (!isset($orderByDivision[$order->getDivisionCode()])) {
                    $orderByDivision[$order->getDivisionCode()] = array($order);
                } else {
                    $orderByDivision[$order->getDivisionCode()][] = $order;
                }
            } else {
                $orderByDivision['division'][] = $order;
            }
        }

        // Generate new invoice file
        if (file_exists($filename)) {
            unlink($filename);
        }

        $this->container->get('knp_snappy.pdf')->generateFromHtml(
            $this->container->get('templating')->render(
                'FoodOrderBundle:Default:corporate_invoice.html.twig',
                array(
                    'orders'  => $orderByDivision,
                    'mainOrder' => $orders[0],
                    'user' => $user
                )
            ),
            $filename
        );
    }

    /**
     * @param Order $order
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function storeUserInvoice($order)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Cannot store invoice PDF without order');
        }

        $s3Client = $this->getS3Client();

        $file = $this->getInvoiceFilename($order);
        $filename = $this->getInvoicePath().$file;

        try {
            $this->container->get('logger')->alert(
                sprintf(
                    'Storing user invoice for Order #%d | with SF data: %s | Filename: %s | Filepath: %s',
                    $order->getId(),
                    $order->getSfLine(),
                    $file,
                    $filename
                )
            );

            $s3Client->putObject(array(
                'Bucket' => $this->container->getParameter('s3_bucket'),
                'Body'   => fopen($filename, 'r'),
                'Key'    => 'pdf/'.$file,
                'ACL'    => 'public-read',
            ));

            unlink($filename);
        } catch (S3Exception $e) {
            throw new \Exception('Error happened while uploading invoice to S3: '.$e->getMessage());
        }
    }

    /**
     * @param Order $order
     * @param string|null $forcedEmail
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function sendUserInvoice($order, $forcedEmail = null)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Cannot send invoice PDF without order');
        }

        if (!empty($forcedEmail)) {
            $emails = array($forcedEmail);
        } else {
            $emails = array();

            $userEmail = $order->getUser()->getEmail();

            if (!$order->getOrderFromNav() || ($userEmail != ($order->getUser()->getPhone().'@foodout.lt'))) {
                $emails[] = $userEmail;
            }

            $placeFinanceMail = $order->getPlacePoint()->getInvoiceEmail();

            if (!empty($placeFinanceMail)) {
                $emails[] = $placeFinanceMail;
            }
        }

        if ($order->getCompany() && $this->container->getParameter('b2b_invoice_email')) {
            $emails[] = $this->container->getParameter('b2b_invoice_email');
        }

        $ml = $this->container->get('food.mailer');
        $logger = $this->container->get('logger');

        $fileName = $this->getInvoiceFilename($order);
        if ($this->container->getParameter('locale') == 'lv') {
            $file = 'https://s3-eu-west-1.amazonaws.com/foodout-lv-invoice/pdf/'.$fileName;
        } else {
            $file = 'https://s3-eu-west-1.amazonaws.com/foodout-invoice/pdf/'.$fileName;
        }

        $variables = array(
            'uzsakymo_data' => $order->getOrderDate()->format("Y-m-d H:i"),
            'restorano_pavadinimas' => $order->getPlaceName(),
        );

        $logger->alert(sprintf(
            'Invoice preparation for sending: Order id: #%d | Invoice: %s | Email count: %d',
            $order->getId(),
            $order->getSfLine(),
            count($emails)
        ));

        if (!empty($emails)) {
            foreach ($emails as $email) {
                $logger->alert(sprintf(
                    'Siunciama saskaita faktura uzsakymui #%d el.pastu: %s. Fakturos failas: %s',
                    $order->getId(),
                    $email,
                    $fileName
                ));

                // TODO this is a temp fix for Mailer lite api
                $ml->removeAttachments()
                    ->resetVariables()
                    ->flush();

                $mailTemplate = $this->container->getParameter('mailer_send_invoice');
                $mailerResponse = $ml->setVariables($variables)
                    ->setRecipient($email, $email)
                    ->addAttachment($fileName, file_get_contents($file))
                    ->setId($mailTemplate)
                    ->send();
                $logger->alert('Mailer responded (for order #' . $order->getId() . '): ' . var_export($mailerResponse, true));

                $variablesForLog = $variables;
                $variablesForLog['filename'] = $fileName;
                $this->container->get('food.order')->logMailSent(
                    $order,
                    'invoice_send',
                    $mailTemplate,
                    $variablesForLog
                );
            }
        }

        return $emails;
    }

    /**
     * @param Order $order
     * @param string|null $forcedEmail
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function sendCorporateInvoice($order, $forcedEmail = null)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Cannot send invoice PDF without order');
        }

        $mailer = $this->container->get('mailer');

        if (!empty($forcedEmail)) {
            $emails = array($forcedEmail);
        } else {
            $emails = array();

            $userEmail = $order->getUser()->getEmail();

            if (!$order->getOrderFromNav() || ($userEmail != ($order->getUser()->getPhone().'@foodout.lt'))) {
                $emails[] = $userEmail;
            }

//            $placeFinanceMail = $order->getPlacePoint()->getInvoiceEmail();

//            if (!empty($placeFinanceMail)) {
//                $emails[] = $placeFinanceMail;
//            }
        }

        $ml = $this->container->get('food.mailer');
        $logger = $this->container->get('logger');

        $fileName = $this->getInvoiceFilename($order);
        if ($this->container->getParameter('locale') == 'lv') {
            $file = 'https://s3-eu-west-1.amazonaws.com/foodout-lv-invoice/pdf/'.$fileName;
        } else {
            $file = 'https://s3-eu-west-1.amazonaws.com/foodout-invoice/pdf/'.$fileName;
        }

        $variables = array(
            'uzsakymo_data' => $order->getOrderDate()->format("Y-m"),
        );

        $logger->alert(sprintf(
            'Invoice preparation for sending: Corporate user id: #%d | Invoice: %s | Email count: %d',
            $order->getUser()->getId(),
            $order->getSfLine(),
            count($emails)
        ));

        if (!empty($emails)) {
            foreach ($emails as $email) {
                $logger->alert(sprintf(
                    'Siunciama saskaita corporate faktura klientui #%d el.pastu: %s. Fakturos failas: %s',
                    $order->getUser()->getId(),
                    $email,
                    $fileName
                ));

                // TODO this is a temp fix for Mailer lite api
                $ml->removeAttachments()
                    ->resetVariables()
                    ->flush();

                $mailTemplate = $this->container->getParameter('mailer_send_corporate_invoice');
                $mailerResponse = $ml->setVariables($variables)
                    ->setRecipient($email, $email)
                    ->addAttachment($fileName, file_get_contents($file))
                    ->setId($mailTemplate)
                    ->send();
                $logger->alert('Mailer responded (for order #' . $order->getId() . '): ' . var_export($mailerResponse, true));

                $variablesForLog = $variables;
                $variablesForLog['filename'] = $fileName;
                $this->container->get('food.order')->logMailSent(
                    $order,
                    'invoice_send',
                    $mailTemplate,
                    $variablesForLog
                );
            }

            // send sf to financing. TODO - translations bitch please
            $message = \Swift_Message::newInstance()
                ->setSubject('Foodout.lt - nauja verslo kliento faktura')
                ->setFrom('info@foodout.lt')
            ;
            $message->addTo('buhalterija@foodout.lt');
//            $message->addTo('karolis.m@foodout.lt');
            $message->setBody('Siunciame kliento '.$order->getUser()->getCompanyName().' jungtine faktura. Prasome isitraukti i NAV');
            $message->attach(\Swift_Attachment::fromPath($file));
            $mailer->send($message);
        }

        return $emails;
    }

    /**
     * @param Order $order
     * @throws \InvalidArgumentException
     */
    public function removeUserInvoice($order)
    {
        $file = $this->getInvoiceFilename($order);
        $filename = $this->getInvoicePath().$file;

        if (file_exists($filename)) {
            unlink($filename);
        } else {
            throw new \InvalidArgumentException('User Invoice does not exist. Can not delete');
        }
    }

    /**
     * @param bool $failOnError
     * @return int|null
     */
    public function getUnusedSfNumber($failOnError = false)
    {
        $doctrine = $this->container->get('doctrine');
        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository('FoodAppBundle:UnusedSfNumbers');

        try {
            $unusedSfNumber = $repo->findOldest();

            if (!$unusedSfNumber || (!$unusedSfNumber instanceof UnusedSfNumbers)) {
                return null;
            }

            $theNumber = $unusedSfNumber->getSfNumber();

            // delete it
            $em->remove($unusedSfNumber);
            $em->flush();

            // return it
            return $theNumber;

        // Rerty on error
        } catch (OptimisticLockException $e) {
            if ($failOnError) {
                return null;
            }

            sleep(1);

            return $this->getUnusedSfNumber(true);
        }
    }
}
