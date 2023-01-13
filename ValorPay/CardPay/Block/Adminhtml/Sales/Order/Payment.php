<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace ValorPay\CardPay\Block\Adminhtml\Sales\Order;

/**
 * Payer Authentication block
 * Class Info
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Payment extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * @return array|\string[]
     */
    public function getAdditionalInformation()
    {
        $payment = $this->getOrder()->getPayment();
	
        $response = [];
        
        if ($payment->getMethod() === "valorpay_gateway") {
		$response['Transaction ID'] = $payment->getData('last_trans_id');
		$response['Approval Code']  = $payment->getData('valor_auth_code');
		$response['RRN']            = $payment->getData('valor_rrn');
        }
        
        return $response;
    }

    /**
     * Render the value as an array
     *
     * @param mixed $value
     * @param bool $escapeHtml
     * @return array
     */
    public function getValueAsArray($value, $escapeHtml = false)
    {
        if (empty($value)) {
            return [];
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        if ($escapeHtml) {
            foreach ($value as $_key => $_val) {
                $value[$_key] = $this->escapeHtml($_val);
            }
        }
        return $value;
    }
    
}