<?php

namespace ValorPay\CardPay\Block\Adminhtml\Sales\Order;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
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
     * Retrieve current order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getOrder();
        $this->getSource();

        if(!$this->getSource()->getValorpayGatewayFee()) {
            return $this;
        }
        $total = new DataObject(
            [
                'code' => 'valorpay_gateway_fee',
                'value' => $this->getSource()->getValorpayGatewayFee(),
                'label' => __($this->surchargeLabel),
            ]
        );
        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}