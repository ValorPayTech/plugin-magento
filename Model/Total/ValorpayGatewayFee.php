<?php
declare(strict_types = 1);
namespace ValorPay\CardPay\Model\Total;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Store\Model\ScopeInterface;

class ValorpayGatewayFee extends AbstractTotal
{

    const TOTAL_CODE = 'valorpay_gateway_fee';
    const BASE_TOTAL_CODE = 'base_valorpay_gateway_fee';

    /**
     * @var float
     */
    private $surchargeIndicator;
    private $surchargeType;
    private $surchargeLabel;
    private $surchargePercentage;
    private $surchargeFlatRate;
    private $baseCurrency;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
    )
    {
        $this->surchargeIndicator = $scopeConfig->getValue('payment/valorpay_gateway/surchargeIndicator', ScopeInterface::SCOPE_STORE);
        $this->surchargeType = $scopeConfig->getValue('payment/valorpay_gateway/surchargeType', ScopeInterface::SCOPE_STORE);
        $this->surchargeLabel = $scopeConfig->getValue('payment/valorpay_gateway/surchargeLabel', ScopeInterface::SCOPE_STORE);
        $this->surchargePercentage = $scopeConfig->getValue('payment/valorpay_gateway/surchargePercentage', ScopeInterface::SCOPE_STORE);
        $this->surchargeFlatRate = $scopeConfig->getValue('payment/valorpay_gateway/surchargeFlatRate', ScopeInterface::SCOPE_STORE);
        
        $currencyCode = $scopeConfig->getValue("currency/options/base", ScopeInterface::SCOPE_WEBSITES);
        $this->baseCurrency =  $currencyFactory->create()->load($currencyCode);
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface
        $shippingAssignment,
        Total $total
    ): ValorpayGatewayFee {
        parent::collect($quote, $shippingAssignment, $total);

        if (count($shippingAssignment->getItems()) == 0) {
            return $this;
        }

        $baseValorPayFee = $this->getFee($quote);
        $currency = $quote->getStore()->getCurrentCurrency();
        $valorPayFee = $this->baseCurrency->convert($baseValorPayFee, $currency);

        $total->setData(static::TOTAL_CODE, $valorPayFee);
        $total->setData(static::BASE_TOTAL_CODE, $baseValorPayFee);

        $total->setTotalAmount(static::TOTAL_CODE, $valorPayFee);
        $total->setBaseTotalAmount(static::TOTAL_CODE, $baseValorPayFee);
	
	// Make sure that quote is also updated
	$quote->setValorpayGatewayFee($valorPayFee);
        $quote->setBaseValorpayGatewayFee($baseValorPayFee);
        
        return $this;
    }

    public function fetch(Quote $quote, Total $total): array
    {
        $base_value = $this->getFee($quote);
        if ($base_value) {
            $currency = $quote->getStore()->getCurrentCurrency();
            $value = $this->baseCurrency->convert($base_value, $currency);
        } else {
            $value = null;
        }
        
        if( $value ) {
        
		return [
		    'code' => static::TOTAL_CODE,
		    'title' => $this->getLabel(),
		    'base_value' => $base_value,
		    'value' => $value
		];
        
        }
        else {
        	
        	return [];
        	
        }
    }

    public function getLabel(): Phrase
    {
        return __($this->surchargeLabel);
    }

    private function getFee(Quote $quote): float
    {
    	
    	if( $this->surchargeIndicator <> 1 ) return (float)null;
    	
        if ($quote->getPayment()->getMethod() !== 'valorpay_gateway') {
           return (float)null;
        }
        
	if( $this->surchargeType == "flatrate" )
        	return (float)$this->surchargeFlatRate;
        else {
        	$total = $quote->getBaseSubtotal();
        	return (float)(($total*$this->surchargePercentage)/100);
        }
    }
}
