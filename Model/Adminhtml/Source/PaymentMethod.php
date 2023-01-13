<?php
/**
 * Copyright © 2022 ValorPay. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace ValorPay\CardPay\Model\Adminhtml\Source;

/**
 * Class PaymentMethod
 */
class PaymentMethod implements \Magento\Framework\Option\ArrayInterface
{
    const ACTION_SALE = 'authorize_capture';
    const ACTION_AUTHORIZE = 'authorize';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_SALE,
                'label' => __('Sale'),
            ],
            [
                'value' => self::ACTION_AUTHORIZE,
                'label' => __('Auth Only'),
            ]
        ];
    }
}