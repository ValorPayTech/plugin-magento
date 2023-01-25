<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ValorPay\CardPay\Block\Form;

/**
 * @api
 * @since 100.0.2
 */
class Cc extends \Magento\Payment\Block\Form
{
    /**
     * @var string
     */
    protected $_template = 'ValorPay_CardPay::form/cc.phtml';

    /**
     * Payment config model
     *
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Stdlib\StringUtils $string,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_paymentConfig = $paymentConfig;
        $version = $productMetadata->getVersion();
        $version_prefix = $string->substr($version,2,1);
        if( $version_prefix >= 4 ) {
        	$this->_template = 'ValorPay_CardPay::form/cc.phtml';
        }
        else {
        	$this->_template = 'ValorPay_CardPay::form/cc_version_2_3.phtml';
        }
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_paymentConfig->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if ($months === null) {
            $months[0] = __('Month');
            $months = array_merge($months, $this->_paymentConfig->getMonths());
            $this->setData('cc_months', $months);
        }
        return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if ($years === null) {
            $years = $this->_paymentConfig->getYears();
            $years = [0 => __('Year')] + $years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }

    /**
     * Retrieve has verification configuration
     *
     * @return bool
     */
    public function hasVerification()
    {
        if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('useccv');
            if ($configData === null) {
                return true;
            }
            return (bool)$configData;
        }
        return true;
    }

    /**
     * Retrieve has avs zip or both configuration
     *
     * @param string $methodCode
     * @return bool
     */
    public function hasAVSZip()
    {
    	$avs_type = $this->getMethod()->getConfigData('avs_type');
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
    public function hasAVSAddress()
    {
    	$avs_type = $this->getMethod()->getConfigData('avs_type');
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
    
    	$show_logo = $this->getMethod()->getConfigData('show_logo');
    	if( $show_logo == 1 )
    		return true;
    	else
    		return false;
    }

    /**
     * Whether switch/solo card type available
     *
     * @deprecated 100.1.0 unused
     * @return bool
     */
    public function hasSsCardType()
    {
        $availableTypes = explode(',', $this->getMethod()->getConfigData('cctypes'));
        $ssPresenations = array_intersect(['SS', 'SM', 'SO'], $availableTypes);
        if ($availableTypes && count($ssPresenations) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Solo/switch card start year
     *
     * @deprecated 100.1.0 unused
     * @return array
     */
    public function getSsStartYears()
    {
        $years = [];
        $first = date("Y");

        for ($index = 5; $index >= 0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        $years = [0 => __('Year')] + $years;
        return $years;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->_eventManager->dispatch('payment_form_block_to_html_before', ['block' => $this]);
        return parent::_toHtml();
    }
}