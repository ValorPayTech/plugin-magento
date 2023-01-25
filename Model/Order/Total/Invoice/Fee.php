<?php
namespace ValorPay\CardPay\Model\Order\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Fee extends AbstractTotal
{
    
    /**
     * Invoice Fee constructor.*/
    public function __construct() {
    }

    /**
     * Collect invoice subtotal
     *
     * @param Invoice $invoice
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $feeAmount = $order->getValorpayGatewayFee();
        $baseFeeAmount = $order->getBaseValorpayGatewayFee();

        $invoice->setValorpayGatewayFee($feeAmount);
        $invoice->setBaseValorpayGatewayFee($baseFeeAmount);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $feeAmount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseFeeAmount);

        return $this;
    }
}
