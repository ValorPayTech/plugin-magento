<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ValorPay\CardPay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Model\CcConfig as CcConfig;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\View\Asset\Repository as Repository; 
use Magento\Checkout\Model\Cart;
use ValorPay\CardPay\Model\CcFactory;
use Magento\Payment\Model\CcConfigProvider;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'valorpay_gateway';
    
    /**
     * @var CcConfig
     */
    protected $ccConfig;
    protected $_ccFactory;

    /**
     * @var MethodInterface[]
     */
    protected $methods = [];
    
    protected $_assetRepo;
    
    private $_billingAddress;

    protected $customerSession;

    protected $iconsProvider;
    protected $_curl;
    protected $_scopeConfig;

    protected $cardCollection;

    /**
	 * Sandbox vault get payment profile URL
	 */
	protected $_WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/getpaymentprofile/%s';
    
    /**
     * @param CcConfig $ccConfig
     * @param PaymentHelper $paymentHelper
     * @param array $methodCodes
     */
    public function __construct(
        CcFactory  $ccFactory,
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        Repository $assetRepo,
        Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \ValorPay\CardPay\Block\Vault\Cc $cardCollection,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        CcConfigProvider $iconsProvider,
        array $methodCodes = []
    ) {
    	$this->_assetRepo = $assetRepo;
	$this->ccConfig = $ccConfig;
    $this->_curl = $curl;
    $this->_scopeConfig = $scopeConfig;
    $this->_ccFactory = $ccFactory;
    $this->iconsProvider  = $iconsProvider;
	$this->_billingAddress = $cart->getQuote()->getBillingAddress();
    $this->customerSession  = $customerSession;
    $this->cardCollection = $cardCollection;
	foreach ($methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
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
                
        $_valor_api_url = sprintf($this->_WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
        if ( 1 === $sandbox ) {
            $_valor_api_url = sprintf($this->_WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
        }
        
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->addHeader("Valor-App-ID", $appid);
        $this->_curl->addHeader("Valor-App-Key", $appkey);
        $this->_curl->addHeader("Accept", "application/json");
        $this->_curl->get($_valor_api_url);
        $response = $this->_curl->getBody();
        
        if ( empty($response) ) return false;

        $parsed_response = json_decode($response);

		return $parsed_response;
		
    }

    public function getStoredCards()
    {
          
        $cardDetails=[];
        if($this->canSaveCard())
        {
            
            $cardTypes          = $this->ccConfig->getCcAvailableTypes();
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
                        
                        $cardDetails[]    = [
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

        return $cardDetails;
        
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
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];

        foreach ($this->methods as $methodCode => $method) {
            if ($method->isAvailable()) {
                $config = array_merge_recursive($config, [
                    'payment' => [
                        'ccform' => [
                            'availableTypes' => [$methodCode => $this->getCcAvailableTypes($methodCode)],
                            'months' => [$methodCode => $this->getCcMonths()],
                            'years' => [$methodCode => $this->getCcYears()],
                            'hasVerification' => [$methodCode => $this->hasVerification($methodCode)],
                            'hasAVSZip' => [$methodCode => $this->hasAVSZip($methodCode)],
			    'hasAVSAddress' => [$methodCode => $this->hasAVSAddress($methodCode)],
			    'showLogo' => [$methodCode => $this->showLogo($methodCode)],
			    'getStreet' => [$methodCode => $this->getStreet()],
			    'getPostcode' => [$methodCode => $this->getPostcode()],
			    'logoImage' => [$methodCode => $this->_assetRepo->getUrl('ValorPay_CardPay::images/ValorPos.png')],
			    'cvvImageUrl' => [$methodCode => $this->getCvvImageUrl()],
                   'canSaveCard' => [$methodCode => $this->canSaveCard()],
                   'showSaveCard' => [$methodCode => $this->showSaveCard($methodCode)],
                   'storedCards' => [$methodCode => $this->getStoredCards()],
                        ]
                    ]
                ]);
            }
        }
        return $config;
    }

    /**
     * Retrieve available credit card types
     *
     * @param string $methodCode
     * @return array
     */
    protected function getCcAvailableTypes($methodCode)
    {
        $types = $this->ccConfig->getCcAvailableTypes();
        $availableTypes = $this->methods[$methodCode]->getConfigData('cctypes');
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach (array_keys($types) as $code) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }
        return $types;
    }

    /**
     * Solo/switch card start years
     *
     * @return array
     * @deprecated 100.1.0 unused
     */
    protected function getSsStartYears()
    {
        return $this->ccConfig->getSsStartYears();
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    protected function getCcMonths()
    {
        return $this->ccConfig->getCcMonths();
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    protected function getCcYears()
    {
        return $this->ccConfig->getCcYears();
    }

    /**
     * Retrieve CVV tooltip image url
     *
     * @return string
     */
    protected function getCvvImageUrl()
    {
        return $this->ccConfig->getCvvImageUrl();
    }

    /**
     * Retrieve has verification configuration
     *
     * @param string $methodCode
     * @return bool
     */
    protected function hasVerification($methodCode)
    {
        $result = $this->ccConfig->hasVerification();
        $configData = $this->methods[$methodCode]->getConfigData('useccv');
        if ($configData !== null) {
            $result = (bool)$configData;
        }
        return $result;
    }

    /**
     * Whether switch/solo card type available
     *
     * @param string $methodCode
     * @return bool
     * @deprecated 100.1.0 unused
     */
    protected function hasSsCardType($methodCode)
    {
        $result = false;
        $availableTypes = explode(',', $this->methods[$methodCode]->getConfigData('cctypes'));
        $ssPresentations = array_intersect(['SS', 'SM', 'SO'], $availableTypes);
        if ($availableTypes && count($ssPresentations) > 0) {
            $result = true;
        }
        return $result;
    }

    /**
     * Retrieve has avs zip or both configuration
     *
     * @param string $methodCode
     * @return bool
     */
    protected function hasAVSZip($methodCode)
    {
    	$avs_type = $this->methods[$methodCode]->getConfigData('avs_type');
    	if( $avs_type == "zip" || $avs_type == "zipandaddress" )
    		return true;
    	else
    		return false;
    }

    /**
     * Retrieve has avs address or both configuration
     *
     * @param string $methodCode
     * @return bool
     */
    protected function hasAVSAddress($methodCode)
    {
    	$avs_type = $this->methods[$methodCode]->getConfigData('avs_type');
    	if( $avs_type == "address" || $avs_type == "zipandaddress" )
    		return true;
    	else
    		return false;
    }

    /**
     * Retrieve show Logo
     *
     * @param string $methodCode
     * @return bool
     */
    protected function showLogo($methodCode)
    {
    
    	$show_logo = $this->methods[$methodCode]->getConfigData('show_logo');
    	if( $show_logo == 1 )
    		return true;
    	else
    		return false;
    }

    /**
     * Retrieve street
     *
     * @param string $methodCode
     * @return string
     */
    protected function getStreet()
    {
    
    	$street = $this->_billingAddress->getData('street');
    	return $street;
    	
    }

    /**
     * Retrieve postcode
     *
     * @param string $methodCode
     * @return string
     */
    protected function getPostcode()
    {
    
        $postcode = $this->_billingAddress->getData('postcode');
    	return $postcode;
    	
    }
    /*added starts*/
    /**
     * Whether to give customers the 'save this card' option, or just assume yes.
     *
     * @return bool
     */
    public function showSaveCard($methodCode)
    {
        return $this->methods[$methodCode]->getConfigData('show_save_card') ? true : false;
    }

    public function getIconUrl($type)
    {
        return isset($this->iconsProvider->getIcons()[$type])?$this->iconsProvider->getIcons()[$type]['url']:'';
    }

    /**
     * @return int
     */
    public function getIconHeight($type)
    {
        return isset($this->iconsProvider->getIcons()[$type])?$this->iconsProvider->getIcons()[$type]['height']:0;
    }

    /**
     * @return int
     */
    public function getIconWidth($type)
    {
        return isset($this->iconsProvider->getIcons()[$type])?$this->iconsProvider->getIcons()[$type]['width']:0;
    }
    
}