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
    
	/**
	 * Vault add customer profile URL
	 */
	protected $_WC_VALORPAY_VAULT_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/addcustomer';
	protected $_WC_VALORPAY_VAULT_PRODUCTION_URL = 'https://online.valorpaytech.com/api/valor-vault/addcustomer';

	/**
	 * Vault add payment profile URL
	 */
	protected $_WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/addpaymentprofile/%s';
	protected $_WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_PRODUCTION_URL = 'https://online.valorpaytech.com/api/valor-vault/addpaymentprofile/%s';

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

	protected $_customer;

	protected $_customerFactory;

	protected $storeManager;
    protected $customerFactory;
    protected $customerResource;
	protected $customerRepoInterface;
	protected $_adminCustomer;

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
		\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResource,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepoInterface,
		\Magento\Sales\Block\Adminhtml\Order\Create\Form\Account $adminCustomer,
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
		
		$this->_customerFactory  = $customerFactory;
        
		$this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
		$this->customerRepoInterface = $customerRepoInterface;
		$this->_adminCustomer = $adminCustomer;

    }
    
    private function get_surcharge_fee($order) 
    {
    
	    $surchargeIndicator  = $this->getConfigData('surchargeIndicator');

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
		'ecomm_channel' => 'magento',
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

        } catch (\Exception $e) {
            $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error. '.$e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error. '.$e->getMessage()));
        }

		if($payment->getAdditionalInformation('save') == 1 && $payment->getCcNumber()){

			$customer_id = $this->customerSession->getCustomer()->getId();

			if($this->_adminCustomer->getCustomerId()){
				$customer_id = $this->_adminCustomer->getCustomerId();
			}

			$_vault_customer_id = $this->get_vault_customer_id($customer_id);

			//if not then create new customer valorpay vault account and get vault customer id
			if (empty($_vault_customer_id)) {
				
				$_vault_customer_id = $this->create_customer_profile( $order, $valor_avs_street, $valor_avs_zip );
				
				$saveCard = $this->_ccFactory->create();

				$saveCard->setData([
					"customer_id" => $customer_id,
					"vault_customer_id" => $_vault_customer_id
				])->save();

			}
			
			//add new card to customer vault account  
			if( $_vault_customer_id ) {
				
				$cardholdername = $order->getBillingAddress()->getName();

				$this->create_payment_profile( $_vault_customer_id, $payment->getCcNumber(), $payment->getCcExpMonth(), $payment->getCcExpYear(), $cardholdername  );	
				
			}
			
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
		'ecomm_channel' => 'magento',
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

        } catch (\Exception $e) {
            if( isset($requestData) ) $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error. '.$e->getMessage()));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error. '.$e->getMessage()));
        }

		if($payment->getAdditionalInformation('save') == 1 && $payment->getCcNumber()){

			$customer_id = $this->customerSession->getCustomer()->getId();

			if($this->_adminCustomer->getCustomerId()){
				$customer_id = $this->_adminCustomer->getCustomerId();
			}
			
			$_vault_customer_id = $this->get_vault_customer_id($customer_id);

			//if not then create new customer valorpay vault account and get vault customer id
			if (empty($_vault_customer_id)) {
				
				$_vault_customer_id = $this->create_customer_profile( $order, $valor_avs_street, $valor_avs_zip );
				
				$saveCard = $this->_ccFactory->create();

				$saveCard->setData([
					"customer_id" => $customer_id,
					"vault_customer_id" => $_vault_customer_id
				])->save();

			}

			//add new card to customer vault account  
			if( $_vault_customer_id ) {
				
				$cardholdername = $order->getBillingAddress()->getName();

				$this->create_payment_profile( $_vault_customer_id, $payment->getCcNumber(), $payment->getCcExpMonth(), $payment->getCcExpYear(), $cardholdername  );	
				
			}
			
		}

        return $this;
    }

    /**
     * Get Vault Customer ID
     *
     * @param int $customer_id
     * @return $_vault_customer_id
     */

	private function get_vault_customer_id($customer_id) {
		$_vault_customer_id = 0;
		$cardModel = $this->_ccFactory->create();
		$collection = $cardModel->getCollection()->addFieldToFilter('customer_id', $customer_id);
		if( count($collection) > 0 ) {
			foreach ($collection as $card) {
				$_vault_customer_id = $card->getVaultCustomerId();
				if (!empty($_vault_customer_id)) break;
			}
		}
		return $_vault_customer_id;
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
	$amount  = number_format($amount,2);

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
		'ecomm_channel' => 'magento',
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
		$response->desc, 
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
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ( !$this->getConfigData('appid') || !$this->getConfigData('appkey') || !$this->getConfigData('epi') ) {
            return false;
        }
 
        return parent::isAvailable($quote);
    }

	/**********************************************************************************************************/
	/******************************** V A U L T  C O D E - S T A R T  H E R E *********************************/
	/******************************************************************************************************** */	
		
		/**
		 * Get the API URL.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		protected function get_valorpay_vault_url($_vault_customer_id) {
			$api_url = $this->_WC_VALORPAY_VAULT_PRODUCTION_URL;
			$sandbox = $this->getConfigData('sandbox');
			if ( !$_vault_customer_id && $sandbox == 1 ) {
				$api_url = $this->_WC_VALORPAY_VAULT_SANDBOX_URL;
			}
			if( $_vault_customer_id ) {
				$api_url = sprintf($this->_WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_PRODUCTION_URL,$_vault_customer_id);
				if ( $sandbox == 1 ) {
					$api_url = sprintf($this->_WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
				}
			}
			return $api_url;
		}
	
		/**
		 * Call valor API
		 *
		 * @since 1.0.0
		 *
		 * @param string $requestData JSON payload.
		 * @param string $_vault_customer_id ValorPay Vault Customer ID.
		 * @param string $list true when listing api called.
		 * @param string $payment_id pass when delete link clicked to delete selected row.
		 * @return string|Error JSON response or a Error on failure.
		 */
    
		private function post_vault_transaction($requestData, $_vault_customer_id=0) 
		{
			
			$response = "";

			$this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
			$this->_curl->addHeader("Valor-App-ID", $this->getConfigData('appid'));
			$this->_curl->addHeader("Valor-App-Key", $this->getConfigData('appkey'));
			$this->_curl->addHeader("Accept", "application/json");
			$this->_curl->addHeader("Content-Type", "application/json");
			$this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
			$this->_valor_api_url = $this->get_valorpay_vault_url($_vault_customer_id);
			$this->_curl->post($this->_valor_api_url, $requestData);
			$response = $this->_curl->getBody();
				
			if ( empty($response) ) return false; 
			
			$parsed_response = json_decode($response);

			return $parsed_response;
		
		}
	
		/**
		 * Create Customer Profile API Action
		 *
		 * @since 1.0.0
		 * @param $order
		 *
		 * @return object JSON response
		 */
		private function create_customer_profile( $order, $valor_avs_street, $valor_avs_zip ) {
			
			/** @var \Magento\Sales\Model\Order\Address $billing */
			$billing = $order->getBillingAddress();
			$shipping = $order->getShippingAddress();
			
			$billing_name       = $billing->getName();
			$billing_company    = $billing->getCompany();
			$billing_phone      = $billing->getTelephone();
			$billing_email      = $order->getCustomerEmail();
			$billing_address    = $valor_avs_street;
			$billing_address2   = $billing->getStreetLine(2);
			$billing_city       = $billing->getCity();
			$billing_state      = $billing->getRegionCode();
			$billing_postcode   = $valor_avs_zip;
			$shipping_name      = $shipping->getName();
			$shipping_address   = $shipping->getStreetLine(1);
			$shipping_address2  = $shipping->getStreetLine(2);
			$shipping_city      = $shipping->getCity();
			$shipping_state     = $shipping->getRegionCode();
			$shipping_postcode  = $shipping->getPostcode();
			
			$payload                                              = array();
			$payload["customer_name"]                             = $billing_name;  
			$payload["company_name"]                              = $billing_company;  
			$payload["customer_phone"]                            = $billing_phone;  
			$payload["customer_email"]                            = $billing_email;
			$payload["address_details"][0]["address_label"]          = "Home";  
			$payload["address_details"][0]["billing_customer_name"]  = $billing_name;
			$payload["address_details"][0]["billing_street_no"]      = $billing_address;
			$payload["address_details"][0]["billing_street_name"]    = $billing_address2;
			$payload["address_details"][0]["billing_zip"]            = $billing_postcode;
			$payload["address_details"][0]["billing_city"]           = $billing_city;
			$payload["address_details"][0]["billing_state"]          = $billing_state;
			$payload["address_details"][0]["shipping_customer_name"] = ($shipping_name?$shipping_name:$billing_name);
			$payload["address_details"][0]["shipping_street_no"]     = ($shipping_address?$shipping_address:$billing_address);
			$payload["address_details"][0]["shipping_street_name"]   = ($shipping_address2?$shipping_address2:($shipping_address?$billing_address2:""));
			$payload["address_details"][0]["shipping_zip"]           = ($shipping_postcode?$shipping_postcode:$billing_postcode);
			$payload["address_details"][0]["shipping_city"]          = ($shipping_city?$shipping_city:$billing_city);
			$payload["address_details"][0]["shipping_state"]         = ($shipping_state?$shipping_state:$billing_state);
			
			$payload = json_encode( $payload );
			$response = $this->post_vault_transaction( $payload );
			
			if( $response && $response->status == "OK" ) {
				return $response->vault_customer_id;	
			}
			
			return 0;
		
		}
	
		/**
		 * Create Payment Profile API Action
		 *
		 * @since 1.0.0
		 * @param int      $_vault_customer_id vault customer id.
		 * @param int      $cc_number credit card number.
		 * @param array    $exp_date credit card expiry date.
		 * @param string   $cc_holdername Card holder name.
		 *
		 * @return object JSON response
		 */	
		public function create_payment_profile( $_vault_customer_id, $cc_number, $exp_month, $exp_year, $cc_holdername ) {
	
			$month = sprintf("%02d", $exp_month);
			$year  = substr( $exp_year, 2 );
	
			$payload                    = array();
			$payload["pan_num"]         = $cc_number;  
			$payload["expiry"]          = "$month/$year";  
			$payload["cardholder_name"] = $cc_holdername;  
			
			$payload  = json_encode( $payload );
			$response = $this->post_vault_transaction( $payload, $_vault_customer_id );
			
			if( $response && $response->status == "OK" ) {
				return $response->payment_id;	
			}
	
			return 0;
	
		}
		
}