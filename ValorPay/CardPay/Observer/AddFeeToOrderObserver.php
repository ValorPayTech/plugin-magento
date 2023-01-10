<?php
namespace ValorPay\CardPay\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddFeeToOrderObserver implements ObserverInterface
{
    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $ValorFee = $quote->getValorpayGatewayFee();
        if (!$ValorFee) {
            return $this;
        }
        //Set fee data to order
        $order = $observer->getOrder();
        $order->setData('valorpay_gateway_fee', $ValorFee);
        $order->setData('base_valorpay_gateway_fee', $ValorFee);
        
	return $this;
    }
}