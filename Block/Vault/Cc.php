<?php
/**
 * Copyright © 2022 ValorPay. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ValorPay\CardPay\Block\Vault;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use ValorPay\CardPay\Model\CcFactory;
use Magento\Customer\Model\Session;
use Magento\Payment\Model\CcConfigProvider;

class Cc extends Template
{
    const CODE = "valorpay_gateway";

    protected $_ccFactory;
    protected $customerSession;
    protected $iconsProvider;

    public function __construct(
        CcFactory  $ccFactory,
        Session $customerSession, 
        CcConfigProvider $iconsProvider,
        Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_ccFactory = $ccFactory;
        $this->customerSession  = $customerSession;
        $this->iconsProvider  = $iconsProvider;
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }

    public function getCardCollection()
    {
        $cardModel = $this->_ccFactory->create();
        $collection = $cardModel->getCollection()->addFieldToFilter('customer_id', $this->getCustomerId());

        return $collection;
    }

    public function getIconUrl($type)
    {
        return $this->iconsProvider->getIcons()[$type]['url'];
    }

    /**
     * @return int
     */
    public function getIconHeight($type)
    {
        return $this->iconsProvider->getIcons()[$type]['height'];
    }

    /**
     * @return int
     */
    public function getIconWidth($type)
    {
        return $this->iconsProvider->getIcons()[$type]['width'];
    }

}
