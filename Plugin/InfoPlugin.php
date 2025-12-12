<?php
namespace ValorPay\CardPay\Plugin;

class InfoPlugin
{
    public function beforeToHtml(\Magento\Payment\Block\Info $subject)
    {
        $subject->setTemplate('ValorPay_CardPay::info/default.phtml');
    }
}
