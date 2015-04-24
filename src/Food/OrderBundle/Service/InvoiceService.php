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
     * @throws \InvalidArgumentException
     */
    public function addInvoiceToSend($order, $mustDoNavDelete=false)
    {
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('I need order to plan invoice generation');
        }

        // Invoice sending is not turned on
        if (!$order->getPlace()->getSendInvoice()) {
            return;
        }

        if ($order->getPlacePointSelfDelivery() || $order->getDeliveryType() == OrderService::$deliveryPickup) {
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

        $ml = $this->container->get('food.mailer');
        $logger = $this->container->get('logger');

        $fileName = $this->getInvoiceFilename($order);
        $file = 'https://s3-eu-west-1.amazonaws.com/foodout-invoice/pdf/'.$fileName;

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

                $mailerResponse = $ml->setVariables($variables)
                    ->setRecipient($email, $email)
                    ->addAttachment($fileName, file_get_contents($file))
                    ->setId($this->container->getParameter('mailer_send_invoice'))
                    ->send();
                $logger->alert('Mailer responded (for order #' . $order->getId() . '): ' . var_export($mailerResponse, true));
            }

            // TODO - panaikinti debugini siuntima...
//            $ml->setVariables($variables)
//                ->setRecipient('mantas@foodout.lt', 'mantas@foodout.lt')
//                ->addAttachment($fileName, file_get_contents($file))
//                ->setId(30019657)
//                ->send();
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
