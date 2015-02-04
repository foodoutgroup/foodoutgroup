<?php
namespace Food\OrderBundle\Tests\Service;

use Food\AppBundle\Test\WebTestCase;
use Food\OrderBundle\Entity\Order;

class InvoiceServiceTest extends WebTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetInvoiceFilenameException()
    {
        $invoiceService = $this->getContainer()->get('food.invoice');

        $invoiceService->getInvoiceFilename(null);
    }

    public function testGetInvoiceFilename()
    {
        $invoiceService = $this->getContainer()->get('food.invoice');

        $order = new Order();
        $order->setSfSeries('FooTest');
        $order->setSfNumber(12245);

        $filename = $invoiceService->getInvoiceFilename($order);

        $this->assertRegexp('#foodout_\w{2}_FooTest12245\.pdf#', $filename);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddInvoiceToSendException()
    {
        $invoiceService = $this->getContainer()->get('food.invoice');

        $invoiceService->addInvoiceToSend(null);
    }

    // TODO Place no send invoice test

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGenerateUserInvoiceException()
    {
        $invoiceService = $this->getContainer()->get('food.invoice');

        $invoiceService->generateUserInvoice(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStoreUserInvoiceException()
    {
        $invoiceService = $this->getContainer()->get('food.invoice');

        $invoiceService->storeUserInvoice(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSendUserInvoiceException()
    {
        $invoiceService = $this->getContainer()->get('food.invoice');

        $invoiceService->sendUserInvoice(null);
    }
}
