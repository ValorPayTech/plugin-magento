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
    protected $cardCollection;

    public function __construct(
        SessionManagerInterface $session,
        \Magento\Framework\App\RequestInterface $request,
        \ValorPay\CardPay\Block\Vault\Cc $cardCollection
    ) {
        $this->session = $session;
        $this->_request = $request;
        $this->cardCollection = $cardCollection;
    }

    public function afterDispatch(\Magento\Multishipping\Controller\Checkout\Overview $subject, $result)
    {
        $token = '';
        $ccLast4 = '';
        $payment = $this->_request->getParam('payment');
        $selectedCard = $this->cardCollection->getCardCollection();

        foreach($selectedCard as $index => $card){
            if($index == $payment["cc_id"])
            {
                $token = $card->getToken();
                $ccLast4 = $card->getCcLast4();
                break;
            }   
        }

        if(isset($payment['cc_id'])){
            $this->session->setToken($token);
            $this->session->setCcLast4($ccLast4);
        }

        if(isset($payment['terms_checked']))
            $this->session->setTermsChecked($payment['terms_checked']);
        else
           $this->session->setTermsChecked(0); 

       if(isset($payment['save']))
            $this->session->setSave($payment['save']);
        else
           $this->session->setSave(0); 

        return $result;
    }
}