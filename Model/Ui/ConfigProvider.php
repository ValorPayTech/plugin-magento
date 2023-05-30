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

    /**
     * @var MethodInterface[]
     */
    protected $methods = [];
    
    protected $_assetRepo;
    
    private $_billingAddress;

    protected $customerSession;

    protected $cardCollection;
    
    /**
     * @param CcConfig $ccConfig
     * @param PaymentHelper $paymentHelper
     * @param array $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        Repository $assetRepo,
        Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \ValorPay\CardPay\Block\Vault\Cc $cardCollection,
        array $methodCodes = []
    ) {
    	$this->_assetRepo = $assetRepo;
	$this->ccConfig = $ccConfig;
	$this->_billingAddress = $cart->getQuote()->getBillingAddress();
    $this->customerSession  = $customerSession;
    $this->cardCollection = $cardCollection;
	foreach ($methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    public function getStoredCards()
    {
          
        $cardDetails=[];
        if($this->canSaveCard())
        {
            if(count($this->cardCollection->getCardCollection())){
                $cardCollection = $this->cardCollection->getCardCollection()
                ->addFieldToSelect(array('cc_id','cc_type','token','cc_exp_month','cc_exp_year','cc_last_4'));

                foreach($cardCollection as $card){
                    $cardDetails[]    = [
                            'cc_id'       => $card->getCcId(),
                            'cc_type'    => $card->getCcType(),
                            'token' => $card->getToken(),
                            'cc_exp_month'     => $card->getCcExpMonth(),
                            'cc_exp_year'     => $card->getCcExpYear(),
                            'cc_last_4'     => $card->getCcLast4(),
                            'type_url'    => $this->cardCollection->getIconUrl($card->getCcType()),
                            'type_width'    => $this->cardCollection->getIconWidth($card->getCcType()),
                            'type_height'    => $this->cardCollection->getIconHeight($card->getCcType()),
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
    
}