<?php
/**
 * Copyright © 2022 ValorPay. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ValorPay\CardPay\Block\Vault;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use ValorPay\CardPay\Model\CcFactory;
use Magento\Customer\Model\Session;
use Magento\Payment\Model\CcConfigProvider;

class Cc extends Template
{
    const CODE = "valorpay_gateway";

    protected $_ccFactory;
    protected $customerSession;
    protected $iconsProvider;
    protected $_curl;
    protected $_scopeConfig;
    protected $_ccConfig;

    /**
	 * Sandbox vault get payment profile URL
	 */
	protected $_WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/getpaymentprofile/%s';

    public function __construct(
        CcFactory  $ccFactory,
        Session $customerSession, 
        CcConfigProvider $iconsProvider,
        Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\CcConfig $ccCongig,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_ccFactory = $ccFactory;
        $this->_curl = $curl;
        $this->_scopeConfig = $scopeConfig;
        $this->_ccConfig = $ccCongig;
        $this->customerSession  = $customerSession;
        $this->iconsProvider  = $iconsProvider;
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
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

    private function post_vault_transaction($_vault_customer_id) 
    {
        $appid = $this->_scopeConfig->getValue('payment/valorpay_gateway/appid',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $appkey = $this->_scopeConfig->getValue('payment/valorpay_gateway/appkey',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $sandbox = $this->_scopeConfig->getValue('payment/valorpay_gateway/sandbox',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $this->_valor_api_url = sprintf($this->_WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
        if ( 1 === $sandbox ) {
            $this->_valor_api_url = sprintf($this->_WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
        }
        
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->addHeader("Valor-App-ID", $appid);
        $this->_curl->addHeader("Valor-App-Key", $appkey);
        $this->_curl->addHeader("Accept", "application/json");
        $this->_curl->get($this->_valor_api_url);
        $response = $this->_curl->getBody();
        
        if ( empty($response) ) return false;

        $parsed_response = json_decode($response);

		return $parsed_response;
		
    }

    /**
     * If card can be saved for further use
     *
     * @return boolean
     */
    public function canSaveCard()
    {
        if ($this->customerSession->isLoggedIn()) {
            return true;
        }

        return false;
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

    public function getCardCollection()
    {
        $dataArray = array();

        if( $this->canSaveCard() )
        {
            
            $cardTypes          = $this->_ccConfig->getCcAvailableTypes();
            $customer_id        = $this->customerSession->getCustomer()->getId();
            $_vault_customer_id = $this->get_vault_customer_id($customer_id);

            $payment_profile    = $this->post_vault_transaction($_vault_customer_id);
            if( isset($payment_profile) && $payment_profile->status == "OK" && count($payment_profile->data) > 0 ) {
                foreach($payment_profile->data as $single_key => $single_data) {

                        $cc_type = "";
                        if( isset($cardTypes) && count($cardTypes) > 0 ) {
                            foreach($cardTypes as $single_key => $single_type) {
                                if( strtolower($single_type) == strtolower($single_data->card_brand) ) {
                                    $cc_type = $single_key;
                                    break;
                                }
                            }
                        }
                        
                        $dataArray[]    = [
                            'cc_id'         => $single_data->payment_id,
                            'cc_type'       => $cc_type,
                            'token'         => $single_data->token,
                            'cc_name'       => $single_data->cardholder_name,
                            'cc_last_4'     => substr($single_data->masked_pan,4),
                            'type_url'      => $this->getIconUrl($cc_type),
                            'type_width'    => $this->getIconWidth($cc_type),
                            'type_height'   => $this->getIconHeight($cc_type),
                        ];

                }

            }
        
        }
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->create('Magento\Framework\Data\Collection');

        foreach ($dataArray as $key => $row) {
            $varienObject = new \Magento\Framework\DataObject();
            $varienObject->setData($row);
            $collection->addItem($varienObject);
        }

        return $collection;
    }

    public function getIconUrl($type)
    {
        return $this->iconsProvider->getIcons()[$type]['url'];
    }

    /**
     * @return int
     */
    public function getIconHeight($type)
    {
        return $this->iconsProvider->getIcons()[$type]['height'];
    }

    /**
     * @return int
     */
    public function getIconWidth($type)
    {
        return $this->iconsProvider->getIcons()[$type]['width'];
    }

}