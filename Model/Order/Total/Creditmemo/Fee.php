<?php
namespace ValorPay\CardPay\Model\Order\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Magento\Store\Model\ScopeInterface;

class Fee extends AbstractTotal
{
    private $surchargeIndicator;
    private $surchargeType;
    private $surchargePercentage;
    
    /**
     * Credit Memo Fee constructor.
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig) {

        $this->surchargeIndicator = $scopeConfig->getValue('payment/valorpay_gateway/surchargeIndicator', ScopeInterface::SCOPE_STORE);
        $this->surchargeType = $scopeConfig->getValue('payment/valorpay_gateway/surchargeType', ScopeInterface::SCOPE_STORE);
        $this->surchargePercentage = $scopeConfig->getValue('payment/valorpay_gateway/surchargePercentage', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $toRefund= $order->getBaseSubtotal() - $order->getBaseSubtotalRefunded();

        // $feeAmountInvoiced = $order->getValorpayGatewayFee();
        // $baseFeeAmountInvoiced = $order->getBaseValorpayGatewayFee();

        $feeAmountInvoiced=0;
        $baseFeeAmountInvoiced=0;
        $itemPrice=0;

        if( $order->getBaseValorpayGatewayFee() ) {
        
            if( $this->surchargeType == "flatrate" ){

                $creditMemoItems = $creditmemo->getAllItems();

                foreach ($creditMemoItems as $creditMemoItem) {

                    $itemPrice += $creditMemoItem->getBaseRowTotal(); 

                }

                if(($itemPrice - $toRefund) == 0 ){
                    $feeAmountInvoiced = $order->getValorpayGatewayFee();
                    $baseFeeAmountInvoiced = $order->getBaseValorpayGatewayFee();
                }
                
            }else {
                
                $feeAmountInvoiced  = $creditmemo->getSubtotal()*($this->surchargePercentage/100);
                $baseFeeAmountInvoiced  = $creditmemo->getBaseSubtotal()*($this->surchargePercentage/100);
            }
        
        }

        // Nothing to refund
        if ((float)$feeAmountInvoiced === 0) {
            return $this;
        }

    $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $feeAmountInvoiced);
    $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseFeeAmountInvoiced);
    $creditmemo->setValorpayGatewayFee($feeAmountInvoiced);
    $creditmemo->setBaseValorpayGatewayFee($baseFeeAmountInvoiced);

        return $this;
    }
}