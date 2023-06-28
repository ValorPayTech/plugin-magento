<?php

namespace ValorPay\CardPay\Plugin\Vault;

use \Magento\Vault\Block\Customer\PaymentTokens;
use ValorPay\CardPay\Block\Vault\Cc;

class VaultCards
{
    protected $ccCollection;

    public function __construct(Cc $ccCollection){
        $this->ccCollection = $ccCollection;
    }  

    public function afterIsExistsCustomerTokens(PaymentTokens $subject, $result)
    {
        if(count($this->ccCollection->getCardCollection())){
            $result=!empty($this->ccCollection->getCardCollection());
        }
        
        return $result;
    }

}