<?php
namespace ValorPay\CardPay\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class UpdatePaymentMethod extends Action implements CsrfAwareActionInterface
{
    protected $resultJsonFactory;
    protected $checkoutSession;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $paymentMethodType = $this->getRequest()->getParam('payment_method_type');

            $quote = $this->checkoutSession->getQuote();
            
            if ($quote && $paymentMethodType) {
                $payment = $quote->getPayment();
                $payment->setAdditionalInformation('payment_method_type', $paymentMethodType);
                $quote->collectTotals()->save();
                
                return $result->setData(['success' => true]);
            }
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'error' => $e->getMessage()]);
        }
        
        return $result->setData(['success' => false]);
    }
    
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
    
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}