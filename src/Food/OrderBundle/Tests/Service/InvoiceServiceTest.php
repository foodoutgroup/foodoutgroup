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

        $this->assertEquals('foodout_lt_FooTest12245.pdf', $filename);
    }
}