<?php
namespace ValorPay\CardPay\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddFeeToOrderObserver implements ObserverInterface
{
	
    const AREA_CODE = \Magento\Framework\App\Area::AREA_ADMINHTML;
	
    protected $_inputParamsResolver;
    protected $_state;
    protected $_request;
    protected $cardCollection;
    
    public function __construct(
    
    	\Magento\Webapi\Controller\Rest\InputParamsResolver $inputParamsResolver,
    	\Magento\Framework\App\State $state,
    	\Magento\Framework\App\Request\Http $request,
    	\ValorPay\CardPay\Block\Vault\Cc $cardCollection

    ) 
    {
    
        $this->_inputParamsResolver = $inputParamsResolver;
        $this->_state = $state;
        $this->_request = $request;
        $this->cardCollection = $cardCollection;
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
		
		$inputParams = $this->_inputParamsResolver->resolve();

		if( isset($inputParams) && count($inputParams) > 0 ) {

			foreach ($inputParams as $inputParam) {
				if ($inputParam instanceof \Magento\Quote\Model\Quote\Payment) {
					$paymentData = $inputParam->getData('additional_data');
					if( isset($paymentData) && count($paymentData) > 0 ) {
						$paymentOrder->setAdditionalInformation('avs_zipcode', $paymentData['avs_zipcode']);
						$paymentOrder->setAdditionalInformation('avs_address', $paymentData['avs_address']);
						$paymentOrder->setAdditionalInformation('terms_checked', $paymentData['terms_checked']);
						$paymentOrder->setAdditionalInformation('save', $paymentData['save']);
						$paymentOrder->setAdditionalInformation('vault_token', $paymentData['vault_token']);
						$paymentOrder->setAdditionalInformation('cc_last_4', $paymentData['cc_last_4']);
					}
				}
			}

		}

	}else{

		$payment_array = $this->_request->getParam('payment');

		if(isset($payment_array['save']))
            $paymentOrder->setAdditionalInformation('save', 1);
        else
           	$paymentOrder->setAdditionalInformation('save', 0); 

		$selectedCard = $this->cardCollection->getCardCollection();

		if((isset($payment_array["cc_id"])) && (count($selectedCard) > $payment_array["cc_id"])) {

			foreach($selectedCard as $index => $card){
	            if($index == $payment_array["cc_id"])
	            {
	            	$paymentOrder->setAdditionalInformation('vault_token', $card->getToken());
	                $paymentOrder->setAdditionalInformation('cc_last_4', $card->getCcLast4());
	                break;
	            }   
	        }
		}
        
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