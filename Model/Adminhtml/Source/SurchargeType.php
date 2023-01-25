<?php
namespace ValorPay\CardPay\Model\Adminhtml\Source;

/**
 * Class SurchargeType
 */
class SurchargeType implements \Magento\Framework\Option\ArrayInterface
{
    const ACTION_PERCENTAGE = 'percentage';
    const ACTION_FLATRATE = 'flatrate';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_PERCENTAGE,
                'label' => __('Surcharge %'),
            ],
            [
                'value' => self::ACTION_FLATRATE,
                'label' => __('Flat Rate $'),
            ]
        ];
    }
}