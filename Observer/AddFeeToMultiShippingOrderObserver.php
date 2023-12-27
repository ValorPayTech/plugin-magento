<?php
namespace ValorPay\CardPay\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;

class AddFeeToMultiShippingOrderObserver implements ObserverInterface
{
	
    const AREA_CODE = \Magento\Framework\App\Area::AREA_ADMINHTML;
	
    protected $_state;
    protected $session;
    
    public function __construct(
    
    	\Magento\Framework\App\State $state,
    	SessionManagerInterface $session

    ) 
    {
    
        $this->_state = $state;
        $this->session = $session;
    }
	
    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$paymentOrder = $observer->getOrder()->getPayment();
    	$quote = $observer->getQuote();
        $paymentQuote = $quote->getPayment();
        $method = $paymentQuote->getMethodInstance()->getCode();

		if ($method != 'valorpay_gateway') return $this;

		//if request processing from front end
		$areaCode = $this->_state->getAreaCode();

		if( $areaCode != self::AREA_CODE ) {

			$terms_checked = $this->session->getTermsChecked();
			$paymentOrder->setAdditionalInformation('terms_checked', $terms_checked);
			$paymentOrder->setAdditionalInformation('vault_token', $this->session->getToken());
			$paymentOrder->setAdditionalInformation('cc_last_4', $this->session->getCcLast4());
			$paymentOrder->setAdditionalInformation('save', $this->session->getSave());
		}
	
		$ValorFee = $quote->getValorpayGatewayFee();
		$BaseValorFee = $quote->getBaseValorpayGatewayFee();

		if (!$ValorFee) {
		    return $this;
		}
		
		$order = $observer->getOrder();
		if( isset($order) ) {
			$order->setData('valorpay_gateway_fee', $ValorFee);
	        	$order->setData('base_valorpay_gateway_fee', $BaseValorFee);
		}
		
		return $this;
    }

}