<?php
namespace ValorPay\CardPay\Model\Adminhtml\Source;

/**
 * Class ValidateKey
 */
class ValidateKey implements \Magento\Framework\App\Config\Value
{
    
    /**
     * @return $this
     */
    public function beforeSave()
    { 
        
        throw new \Magento\Framework\Exception\ValidatorException(__('ValorPay API Keys are not valid.'));

    }

}