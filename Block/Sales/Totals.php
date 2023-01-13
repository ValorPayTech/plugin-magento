<?php declare(strict_types=1);

namespace ValorPay\CardPay\Block\Sales;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
	
class Totals extends Template
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var DataObject
     */
    protected $_source;
    
    private $surchargeLabel;
        
    public function __construct(
    	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
            $this->surchargeLabel = $scopeConfig->getValue('payment/valorpay_gateway/surchargeLabel', ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * Check if we nedd display full tax total info
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->_source;
    }
    public function getStore()
    {
        return $this->_order->getStore();
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }
    /**
     * Initialize payment fee totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();
        
        if (!$this->_source->getValorpayGatewayFee()) {
            return $this;
        }

        $fee = new DataObject(
            [
                'code' => 'valorpay_gateway_fee',
                'strong' => false,
                'value' => $this->_source->getValorpayGatewayFee(),
                'label' => __($this->surchargeLabel),
            ]
        );

        $parent->addTotal($fee, 'fee');

        return $this;
    }
}
