<?php 
namespace ValorPay\CardPay\Model\ResourceModel;

class Cc extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
 public function _construct(){
 	$this->_init("valorpay_vault","cc_id");
 }
}