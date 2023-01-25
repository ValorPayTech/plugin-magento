<?php
namespace ValorPay\CardPay\Model\Order\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class Fee extends AbstractTotal
{
    
    /**
     * Credit Memo Fee constructor.
     */
    public function __construct() {
    }

    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $feeAmountInvoiced = $order->getValorpayGatewayFee();
        $baseFeeAmountInvoiced = $order->getBaseValorpayGatewayFee();

        // Nothing to refound
        if ((int)$feeAmountInvoiced === 0) {
            return $this;
        }

	$creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $feeAmountInvoiced);
	$creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseFeeAmountInvoiced);
	$creditmemo->setValorpayGatewayFee($feeAmountInvoiced);
	$creditmemo->setBaseValorpayGatewayFee($baseFeeAmountInvoiced);

        return $this;
    }
}