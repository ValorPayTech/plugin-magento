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
final class ConfigProvider implements ConfigProviderInterface
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
        array $methodCodes = []
    ) {
    	$this->_assetRepo = $assetRepo;
	$this->ccConfig = $ccConfig;
	$this->_billingAddress = $cart->getQuote()->getBillingAddress();
	foreach ($methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
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
			    'cvvImageUrl' => [$methodCode => $this->getCvvImageUrl()]
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
    
}