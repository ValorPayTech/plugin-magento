<?php 
namespace ValorPay\CardPay\Model\ResourceModel\Cc;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    public function _construct(){
        $this->_init("ValorPay\CardPay\Model\Cc","ValorPay\CardPay\Model\ResourceModel\Cc");
    }
}