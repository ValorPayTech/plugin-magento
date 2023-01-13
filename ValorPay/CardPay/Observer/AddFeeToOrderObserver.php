<?php
namespace ValorPay\CardPay\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddFeeToOrderObserver implements ObserverInterface
{
	
    protected $_inputParamsResolver;
    
    public function __construct(
    
    	\Magento\Webapi\Controller\Rest\InputParamsResolver $inputParamsResolver
    
    ) 
    {
    
        $this->_inputParamsResolver = $inputParamsResolver;
        
    }
	
    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        
        $inputParams = $this->_inputParamsResolver->resolve();
	    
	foreach ($inputParams as $inputParam) {
		if ($inputParam instanceof \Magento\Quote\Model\Quote\Payment) {
			$paymentData = $inputParam->getData('additional_data');
			$paymentOrder = $observer->getOrder()->getPayment();
			$paymentQuote = $quote->getPayment();
			$method = $paymentQuote->getMethodInstance()->getCode();
			if ($method == 'valorpay_gateway') {
				$paymentOrder->setAdditionalInformation('avs_zipcode', $paymentData['avs_zipcode']);
				$paymentOrder->setAdditionalInformation('avs_address', $paymentData['avs_address']);
			}
		}
	}
	
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