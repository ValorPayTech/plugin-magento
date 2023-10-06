<?php
/**
 * ValorPay_CardPay payment method model
 *
 * @category    ValorPay
 * @package     ValorPay_CardPay
 */

namespace ValorPay\CardPay\Model;

class Payment extends \ValorPay\CardPay\Model\Method\Cc
{
    const CODE = 'valorpay_gateway';
    
    const PAYMENT_METHOD_VALORPAY_GATEWAY_CODE = 'valorpay_gateway';
    
    protected $_code = self::PAYMENT_METHOD_VALORPAY_GATEWAY_CODE;
    
    protected $_curl;
    protected $_valor_api_url        = 'https://securelink.valorpaytech.com/';
    
    protected $_remoteAddress;	
    protected $_orderRepository;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;

    protected $_countryFactory;
    protected $_request;
    protected $_data;

    protected $_ccFactory;
    protected $customerSession;
    
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\App\Request\Http $request,
        \ValorPay\CardPay\Model\CcFactory $ccFactory,
    	\Magento\Customer\Model\Session $customerSession,
        array $data = array()
    ) {
		
		parent::__construct(
		    $context,
		    $registry,
		    $extensionFactory,
		    $customAttributeFactory,
		    $paymentData,
		    $scopeConfig,
		    $logger,
		    $moduleList,
		    $localeDate,
		    null,
		    null,
		    $data
		);
	
		$this->_data = $data;
	
		$this->_request = $request;
	
		$this->_curl = $curl;

		$this->_countryFactory = $countryFactory;

		$this->_remoteAddress = $remoteAddress;

		$this->_orderRepository = $orderRepository;

		$this->_ccFactory = $ccFactory;
        $this->customerSession  = $customerSession;
        
    }
    
    private function get_surcharge_fee($order) 
    {
    
	    $surchargeIndicator  = $this->getConfigData('surchargeIndicator');
	    $surchargeType       = $this->getConfigData('surchargeType');
	    $surchargeFlatRate   = $this->getConfigData('surchargeFlatRate');
	    $surchargePercentage = $this->getConfigData('surchargePercentage');

	    if( $surchargeIndicator == 1 ) {
		
			$surchargeAmount = $order->getData('base_valorpay_gateway_fee');
		
	    } else {

			$surchargeAmount    = 0;
	    
		}
	    
	    return $surchargeAmount;
    
    }
    
    private function post_transaction($requestData,$refundRequest=0) 
    {
		
		$sandbox = $this->getConfigData('sandbox');

    	$this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
    	$this->_curl->addHeader("Content-Type", "application/json");
    	    
		if( $sandbox == 1 )	{
			
			$this->_valor_api_url = 'https://securelink-staging.valorpaytech.com:4430';
			$this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
		
		}

    	$this->_curl->post($this->_valor_api_url, json_encode($requestData));
		
	    //response will contain the output of curl request
	    $response = $this->_curl->getBody();
	    
	    $response = json_decode($response);
	    
	    if( $response->error_no != "S00" ) {
	    	
	    	$error_message = $response->mesg;
			if( isset($response->desc) )
	    		$error_message .= " ".$response->desc;
	    	
	    	throw new \Magento\Framework\Validator\Exception(__($error_message));
	    	
	    }
	    
	    return $response;
    
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
		
	try {
	    
	    $surchargeIndicator = $this->getConfigData('surchargeIndicator');
	    
	    if( $surchargeIndicator != 1 ) $surchargeIndicator = 0;
            
            $surchargeAmount = $this->get_surcharge_fee($order);
            
            $amount = $amount - $surchargeAmount - $order->getBaseTaxAmount();
            
            $payment_array = $this->_request->getParam('payment');
	    
	    if( isset($payment_array["avs_zipcode"]) && strlen($payment_array["avs_zipcode"]) > 0 ) //if request post from admin then it work
	    	$avs_zipcode = $payment_array["avs_zipcode"];
	    else
	    	$avs_zipcode = $payment->getAdditionalInformation("avs_zipcode"); // if request post from front end then it work
	    	
	    if( isset($payment_array["avs_address"]) && strlen($payment_array["avs_address"]) > 0 ) 
	    	$avs_address   = $payment_array["avs_address"];
	    else
	    	$avs_address = $payment->getAdditionalInformation("avs_address");
            
	    $valor_avs_street = ($avs_address?$avs_address:$billing->getStreetLine(1));
            $valor_avs_zip = ($avs_zipcode?$avs_zipcode:$billing->getPostcode());

        if($payment->getAdditionalInformation('vault_token')){
        	$expirydate = '';
        	$payment->setData('cc_last_4', $payment->getAdditionalInformation('cc_last_4'));
        }else{
        	$expirydate = sprintf('%02d',$payment->getCcExpMonth()).substr($payment->getCcExpYear(),2,2);
        }    
            
            $requestData = array(
		'appid' => $this->getConfigData('appid'),
		'appkey' => $this->getConfigData('appkey'),
		'epi' => $this->getConfigData('epi'),
		'txn_type' => 'auth',
		'amount' => $amount,
		'sandbox' => $this->getConfigData('sandbox'),
		'phone' => $billing->getTelephone(),
		'email' => $order->getCustomerEmail(),
		'uid' => $order->getIncrementId(),
		'tax_amount' => $order->getBaseTaxAmount(),
		'ip' => $this->_remoteAddress->getRemoteAddress(),
		'surchargeIndicator' => $surchargeIndicator,
		'surchargeAmount' => $surchargeAmount,
		'address1' => $valor_avs_street,
		'address2' => $billing->getStreetLine(2),
		'city' => $billing->getCity(),
		'state' => $billing->getRegion(),
		'zip' => $valor_avs_zip,
		'billing_country' => $billing->getCountryId(),
		'shipping_country' => $shipping->getCountryId(),
		'cardnumber' => $payment->getCcNumber(),
		'status' => 'Y',
		'cvv' => $payment->getCcCid(),
		'cardholdername' => $billing->getName(),
		'expirydate' => $expirydate,
		'terms_checked' => $payment->getAdditionalInformation('terms_checked'),
		'token' => $payment->getAdditionalInformation('vault_token')
            );
            
            $response = $this->post_transaction($requestData);
            
            $payment
                ->setTransactionId($response->txnid)
                ->setIsTransactionClosed(0);
                
            $payment->setData('valor_token', $response->token);
            $payment->setData('valor_rrn', $response->rrn);
            $payment->setData('valor_auth_code', $response->approval_code);
    
            $response_string = sprintf(
		/* translators: 1: Error Message, 2: Amount, 3: Line Break, 4: Approval Code, 5: Line Break, 6: RRN Number. */
		__( 'ValorPos payment %1$s for %2$s.%3$s <strong>Transaction ID:</strong>  %4$s.%5$s <strong>Approval Code:</strong> %6$s.%7$s <strong>RRN:</strong> %8$s' ), 
		"authorized",
		$order->getBaseCurrency()->formatTxt($amount + $surchargeAmount),
		"<br />",
		$response->txnid,
		"<br />",
		$response->approval_code,
		"<br />",
		$response->rrn
	    );
	    
            $order->addCommentToStatusHistory($response_string);
	    $this->_orderRepository->save($order);

		    if($payment->getAdditionalInformation('save')==1 && $payment->getCcNumber()){

				$customer_id=$this->customerSession->getCustomer()->getId();
				$saveCard = $this->_ccFactory->create();

				$saveCard->setData([
					"customer_id" => $customer_id,
					"cc_type" => $payment->getCcType(),
					"cc_last_4" => substr($payment->getCcNumber(), -4),
					"cc_exp_month" => sprintf("%02d", $payment->getCcExpMonth()),
					"cc_exp_year" => $payment->getCcExpYear(),
					"token" => $response->token
				]);

	        	$saveCard->save();
			}
    
        } catch (\Exception $e) {
            $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error. '.$e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error. '.$e->getMessage()));
        }

        return $this;
    }

    /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
	
	/** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
		
	try {
	    
	    $surchargeIndicator  = $this->getConfigData('surchargeIndicator');
	    
	    if( $surchargeIndicator != 1 ) $surchargeIndicator = 0;
	                
            $surchargeAmount = $this->get_surcharge_fee($order);
            
            $amount = $amount - $surchargeAmount - $order->getBaseTaxAmount();
            
            $payment_array = $this->_request->getParam('payment');
	    
	    if( isset($payment_array["avs_zipcode"]) && strlen($payment_array["avs_zipcode"]) > 0 ) //if request post from admin then it work
	    	$avs_zipcode = $payment_array["avs_zipcode"];
	    else
	    	$avs_zipcode = $payment->getAdditionalInformation("avs_zipcode"); // if request post from front end then it work
	    	
	    if( isset($payment_array["avs_address"]) && strlen($payment_array["avs_address"]) > 0 ) 
	    	$avs_address   = $payment_array["avs_address"];
	    else
	    	$avs_address = $payment->getAdditionalInformation("avs_address");
	    
	    $valor_avs_street = ($avs_address?$avs_address:$billing->getStreetLine(1));
            $valor_avs_zip = ($avs_zipcode?$avs_zipcode:$billing->getPostcode());

        if($payment->getAdditionalInformation('vault_token')){
        	$expirydate = '';
        	$payment->setData('cc_last_4', $payment->getAdditionalInformation('cc_last_4'));
        }else{
        	$expirydate = sprintf('%02d',$payment->getCcExpMonth()).substr($payment->getCcExpYear(),2,2);
        }    
            
            $requestData = array(
		'appid' => $this->getConfigData('appid'),
		'appkey' => $this->getConfigData('appkey'),
		'epi' => $this->getConfigData('epi'),
		'txn_type' => 'sale',
		'amount' => $amount,
		'sandbox' => $this->getConfigData('sandbox'),
		'phone' => $billing->getTelephone(),
		'email' => $order->getCustomerEmail(),
		'uid' => $order->getIncrementId(),
		'tax_amount' => $order->getBaseTaxAmount(),
		'ip' => $this->_remoteAddress->getRemoteAddress(),
		'surchargeIndicator' => $surchargeIndicator,
		'surchargeAmount' => $surchargeAmount,
		'address1' => $valor_avs_street,
		'address2' => $billing->getStreetLine(2),
		'city' => $billing->getCity(),
		'state' => $billing->getRegion(),
		'zip' => $valor_avs_zip,
		'billing_country' => $billing->getCountryId(),
		'shipping_country' => $shipping->getCountryId(),
		'cardnumber' => $payment->getCcNumber(),
		'status' => 'Y',
		'cvv' => $payment->getCcCid(),
		'cardholdername' => $billing->getName(),
		'expirydate' => $expirydate,
		'terms_checked' => $payment->getAdditionalInformation('terms_checked'),
		'token' => $payment->getAdditionalInformation('vault_token')
            );
            
            $response = $this->post_transaction($requestData);
	    
            $payment
                ->setTransactionId($response->txnid)
                ->setIsTransactionClosed(0);
            
            $payment->setData('valor_token', $response->token);
            $payment->setData('valor_rrn', $response->rrn);
            $payment->setData('valor_auth_code', $response->approval_code);

            $response_string = sprintf(
		/* translators: 1: Error Message, 2: Amount, 3: Line Break, 4: Approval Code, 5: Line Break, 6: RRN Number. */
		__( 'ValorPos payment %1$s for %2$s.%3$s <strong>Transaction ID:</strong>  %4$s.%5$s <strong>Approval Code:</strong> %6$s.%7$s <strong>RRN:</strong> %8$s'), 
		"completed",
		$order->getBaseCurrency()->formatTxt($amount + $surchargeAmount),
		"<br />",
		$response->txnid,
		"<br />",
		$response->approval_code,
		"<br />",
		$response->rrn
	    );
            
            $order->addCommentToStatusHistory($response_string);
	    	$this->_orderRepository->save($order);  

		    if($payment->getAdditionalInformation('save')==1 && $payment->getCcNumber()){

				$customer_id=$this->customerSession->getCustomer()->getId();
				$saveCard = $this->_ccFactory->create();

				$saveCard->setData([
					"customer_id" => $customer_id,
					"cc_type" => $payment->getCcType(),
					"cc_last_4" => substr($payment->getCcNumber(), -4),
					"cc_exp_month" => sprintf("%02d", $payment->getCcExpMonth()),
					"cc_exp_year" => $payment->getCcExpYear(),
					"token" => $response->token
				]);

	        	$saveCard->save();
			}

        } catch (\Exception $e) {
            if( isset($requestData) ) $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error. '.$e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error. '.$e->getMessage()));
        }

        return $this;
    }

    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();
	
	/** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        
	$token = $payment->getData('valor_token');
	$rrn = $payment->getData('valor_rrn');
	$auth_code = $payment->getData('valor_auth_code');
	
	$creditmemo_array = $this->_request->getParam('creditmemo');

	$otp  = $creditmemo_array["refund_otp_no"];
	$uuid = $creditmemo_array["uuid"];
        
        try {
            
            $surchargeIndicator  = $this->getConfigData('surchargeIndicator');
            
            if( $surchargeIndicator != 1 ) $surchargeIndicator = 0;
            
            $requestData = array(
		'appid' => $this->getConfigData('appid'),
		'appkey' => $this->getConfigData('appkey'),
		'epi' => $this->getConfigData('epi'),
		'txn_type' => 'refund',
		'amount' => $amount,
		'sandbox' => $this->getConfigData('sandbox'),
		'token' => $token,
		'ref_txn_id' => $transactionId,
		'rrn' => $rrn,
		'ip' => $this->_remoteAddress->getRemoteAddress(),
		'auth_code' => $auth_code,
		'surchargeIndicator' => $surchargeIndicator,
		'otp' => $otp,
		'uuid' => $uuid
	    );
	    
	    $response = $this->post_transaction($requestData,1);

	    $payment
	    	->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
	    	->setParentTransactionId($transactionId)
	    	->setIsTransactionClosed(1)
	    	->setShouldCloseParentTransaction(1);
	    
	    $response_string = sprintf(
		__( 'ValorPos Refund for %1$s.%2$s <strong>Transaction ID:</strong>  %3$s.%4$s <strong>Approval Code:</strong> %5$s.%6$s <strong>RRN:</strong> %7$s' ), 
		$order->getBaseCurrency()->formatTxt($response->amount),
		"<br />",
		$response->txnid,
		"<br />",
		$response->approval_code, 
		"<br />",
		$response->rrn
	    );
				
	    $order->addCommentToStatusHistory($response_string);
	    $this->_orderRepository->save($order);
	    
        } catch (\Exception $e) {
            
            if( isset($requestData) ) 
            	$this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
	    $this->_logger->error(__('Payment refunding error. '.$e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error. '.$e->getMessage()));
            
        }

        return $this;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ( !$this->getConfigData('appid') || !$this->getConfigData('appkey') || !$this->getConfigData('epi') ) {
            return false;
        }
 
        return parent::isAvailable($quote);
    }
}