<?php
/**
 * Copyright © 2022 ValorPay. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ValorPay\CardPay\Plugin\Multishipping;

use Magento\Framework\Session\SessionManagerInterface;

class Overview
{
    protected $session;
    protected $_request;

    public function __construct(
        SessionManagerInterface $session,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->session = $session;
        $this->_request = $request;
    }

    public function afterDispatch(\Magento\Multishipping\Controller\Checkout\Overview $subject, $result)
    {
        $payment = $this->_request->getParam('payment');

        if(isset($payment['terms_checked']))
            $this->session->setTermsChecked($payment['terms_checked']);
        else
           $this->session->setTermsChecked(0); 

        return $result;
    }
}