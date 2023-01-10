<?php
/**
 * Payment Card Types Source Model
 *
 * @category    
 * @package     
 * @author      
 * @copyright   
 * @license    
 */

namespace ValorPay\CardPay\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB', 'DN');
    }
}