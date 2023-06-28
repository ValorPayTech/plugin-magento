<?php 
namespace ValorPay\CardPay\Model;

class Cc extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ValorPay\CardPay\Model\ResourceModel\Cc");
	}
}