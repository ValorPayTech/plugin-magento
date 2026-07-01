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
    protected $encryptor;
    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);
        $this->encryptor = $encryptor;
    }

    /**
     * @return array|\string[]
     */
    public function getAdditionalInformation()
    {
        $payment = $this->getOrder()->getPayment();
	
        $response = [];

        if(null !== $payment->getAdditionalInformation('account_number')){
            $raw_account = $payment->getAdditionalInformation('account_number');
            $raw_routing = $payment->getAdditionalInformation('routing_number');

            $account_number = $this->isEncrypted($raw_account) ? $this->encryptor->decrypt($raw_account) : $raw_account;
            $routing_number = $this->isEncrypted($raw_routing) ? $this->encryptor->decrypt($raw_routing) : $raw_routing;
            $masked_account_number = preg_replace('/\d(?=(?:.*\d){4})/', '*', $account_number);
            $masked_routing_number = preg_replace('/\d(?=(?:.*\d){4})/', '*', $routing_number);
            $valorAuthCode = $payment->getData('valor_auth_code');

            $response['Approval Code'] = $valorAuthCode;
            $response['Reference Number'] = $payment->getData('valor_rrn');
            trim($valorAuthCode) === 'Waiting for file to be uploaded' ? $response['Verification Status'] = $payment->getData('valor_ach_verification_status') : $response['Document ID'] = $payment->getData('last_trans_id');
            $response['Account Number'] = $masked_account_number;
            $response['Routing Number'] = $masked_routing_number;
        }
        elseif ($payment->getMethod() === "valorpay_gateway") {
            $response['Transaction ID'] = $payment->getData('last_trans_id');
            $response['Approval Code']  = $payment->getData('valor_auth_code');
            $response['RRN']            = $payment->getData('valor_rrn');
        }
        
        return $response;
    }

    private function isEncrypted(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }
        return (bool) preg_match('/^\d+:\d+:.+$/', $value);
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
