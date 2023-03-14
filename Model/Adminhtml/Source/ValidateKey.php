<?php
namespace ValorPay\CardPay\Model\Adminhtml\Source;

/**
 * Class ValidateKey
 */
class ValidateKey 
{
    
    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
        
        throw new \Magento\Framework\Exception\ValidatorException(__('ValorPay API Keys are not valid.'));
        
        return $proceed();
    }

}