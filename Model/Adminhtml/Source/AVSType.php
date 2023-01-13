<?php
/**
 * Copyright © 2022 ValorPay. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace ValorPay\CardPay\Model\Adminhtml\Source;

/**
 * Class AVSType
 */
class AVSType implements \Magento\Framework\Option\ArrayInterface
{
    const AVSTYPE_NONE = 'none';
    const AVSTYPE_ZIP = 'zip';
    const AVSTYPE_ADDRESS = 'address';
    const AVSTYPE_ZIPANDADDRESS = 'zipandaddress';	

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AVSTYPE_NONE,
                'label' => __('None'),
            ],
            [
                'value' => self::AVSTYPE_ZIP,
                'label' => __('Zip Only'),
            ],
            [
                'value' => self::AVSTYPE_ADDRESS,
                'label' => __('Address Only'),
            ],
            [
                'value' => self::AVSTYPE_ZIPANDADDRESS,
                'label' => __('Zip & Address'),
            ]
        ];
    }
}