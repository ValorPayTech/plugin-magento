<?php

namespace ValorPay\CardPay\Controller\Adminhtml\SendOtp;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    private $invoiceRepository;
    private $creditmemoDocumentFactory;
    
    protected $_curl;
    protected $_valor_api_url = 'http://localhost:7000/v1/sendotp';
    
    public function __construct(
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\Order\CreditmemoDocumentFactory $creditmemoDocumentFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory

    )
    {
        parent::__construct($context);
        $this->_curl = $curl;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoDocumentFactory = $creditmemoDocumentFactory;
    }

    public function execute()
    { 
        $invoiceId,
	array $items = [],
	$notify = false,
	$appendComment = false,
	\Magento\Sales\Api\Data\CreditmemoCommentCreationInterface $comment = null,
        \Magento\Sales\Api\Data\CreditmemoCreationArgumentsInterface $arguments = null
    ) {
    	
    	$invoice = $this->invoiceRepository->get($invoiceId);
	$creditmemo = $this->creditmemoDocumentFactory->createFromInvoice(
	    $invoice,
	    $items,
	    $comment,
	    ($appendComment && $notify),
	    $arguments
        );
	
	$refundamount = $creditmemo->getBaseGrandTotal();
	
	$requestData = array(
	   'appid' => $this->getConfigData('appid'),
	   'appkey' => $this->getConfigData('appkey'),
	   'epi' => $this->getConfigData('epi'),
	   'amount' => $refundamount,
	   'sandbox' => $this->getConfigData('sandbox')
        );
	
	$this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
	$this->_curl->post($this->_valor_api_url, $requestData);
	
	//response will contain the output of curl request
	$response = $this->_curl->getBody();
	
	$masked_email = $response->emailId;
	$masked_phone = $response->phoneNumber;
						
        $resultJson = $this->resultJsonFactory->create();
	return $resultJson->setData([
            'message' => '<span>'. sprintf(__('OTP sent to your registered Email Address %1$s and Mobile Number %2$s'), '<b>'.$masked_email.'</b>', '<b>'.$masked_phone.'</b>') .' </span>',
            'error'   => false
        ]);
    }
}