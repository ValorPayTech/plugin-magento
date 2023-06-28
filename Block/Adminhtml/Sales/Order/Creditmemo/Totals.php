<?php declare(strict_types=1);

namespace ValorPay\CardPay\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Totals extends Template
{

    private $surchargeLabel;
    
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->surchargeLabel = $scopeConfig->getValue('payment/valorpay_gateway/surchargeLabel', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get data (totals) source model
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }
    /**
     * Initialize payment fee totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getCreditmemo();
        $this->getSource();

        if(!$this->getSource()->getValorpayGatewayFee()) {
            return $this;
        }
        $fee = new DataObject(
            [
                'code' => 'valorpay_gateway_fee',
                'strong' => false,
                'value' => $this->getSource()->getValorpayGatewayFee(),
                'base_value' => $this->getSource()->getBaseValorpayGatewayFee(),
                'label' => __($this->surchargeLabel),
            ]
        );

        $this->getParentBlock()->addTotalBefore($fee, 'grand_total');

        return $this;
    }
}