<?php declare(strict_types=1);

namespace ValorPay\CardPay\Block\Adminhtml\Sales\Order\Invoice;

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

    /**
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }
    /**
     * Initialize payment fee totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getInvoice();
        $this->getSource();

        if(!$this->getSource()->getValorpayGatewayFee()) {
            return $this;
        }
        $total = new DataObject(
            [
                'code' => 'valorpay_gateway_fee',
                'value' => $this->getSource()->getValorpayGatewayFee(),
                'base_value' => $this->getSource()->getBaseValorpayGatewayFee(),
                'label' => __($this->surchargeLabel),
            ]
        );

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');
        return $this;
    }
}