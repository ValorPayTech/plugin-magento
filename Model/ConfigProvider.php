<?php
namespace ValorPay\CardPay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const XML_PATH_ENABLE_ACH  = 'payment/valorpay_gateway/enable_ach';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Pass config to checkoutConfig JS object
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'valorpay' => [
                    'enableAch'  => $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_ACH, ScopeInterface::SCOPE_STORE),
                ]
            ]
        ];
    }
}
