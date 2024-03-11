<?php
namespace ValorPay\CardPay\Controller\Vault;

use ValorPay\CardPay\Model\CcFactory;
use Magento\Framework\Controller\ResultFactory;

class DeleteAction extends \Magento\Framework\App\Action\Action
{
    protected $resultFactory;
    protected $_ccFactory;
    protected $customerSession;

    /**
    
    * vault delete payment profile URL
    */
    protected $_WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/deletepaymentprofile/%s/%s';
    protected $_WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_PRODUCTION_URL = 'https://online.valorpaytech.com/api/valor-vault/deletepaymentprofile/%s/%s';
   
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ResultFactory $resultFactory,
        CcFactory  $ccFactory
    )
    {
        $this->resultFactory = $resultFactory;
        $this->_ccFactory = $ccFactory;
        parent::__construct($context);
    }
    
    /**
     * Call valor API
     *
     * @since 1.0.0
     *
     * @param string $requestData JSON payload.
     * @param string $_vault_customer_id ValorPay Vault Customer ID.
     * @return string|Error JSON response or a Error on failure.
     */

    private function post_vault_transaction($_vault_customer_id, $payment_id) 
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $curl = $objectManager->get(\Magento\Framework\HTTP\Client\Curl::class);
        
        $appid = $scopeConfig->getValue('payment/valorpay_gateway/appid',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $appkey = $scopeConfig->getValue('payment/valorpay_gateway/appkey',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $sandbox = $scopeConfig->getValue('payment/valorpay_gateway/sandbox',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $_valor_api_url = sprintf($this->_WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_PRODUCTION_URL,$_vault_customer_id,$payment_id);
        if ( 1 === $sandbox ) {
            $_valor_api_url = sprintf($this->_WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id,$payment_id);
        }
        
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $curl->addHeader("Valor-App-ID", $appid);
        $curl->addHeader("Valor-App-Key", $appkey);
        $curl->addHeader("Accept", "application/json");
        $curl->get($_valor_api_url);
        $response = $curl->getBody();
        if ( empty($response) ) return false;

        $parsed_response = json_decode($response);

		return $parsed_response;
		
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

    public function execute()
    {
       try {
            $cc_id = $this->getRequest()->getParam("cc_id");
            if ($cc_id) {
                
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
                $customerSession = $objectManager->get(\Magento\Customer\Model\Session::class); 
                $customer = $customerSession->getCustomer();

                $customer_id = $customer->getId();
                $_vault_customer_id = $this->get_vault_customer_id($customer_id);
                
                $payment_profile    = $this->post_vault_transaction($_vault_customer_id, $cc_id);
                if( isset($payment_profile) && $payment_profile->status == "OK" ) {
                    $this->messageManager->addSuccessMessage(__("Stored Payment Method was successfully removed."));
                }

            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("Deletion failure. Please try again."));
        }

        return $this->_redirect('vault/cards/listaction');
    }
}