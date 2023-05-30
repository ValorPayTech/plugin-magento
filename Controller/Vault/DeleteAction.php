<?php
namespace ValorPay\CardPay\Controller\Vault;

use ValorPay\CardPay\Model\CcFactory;
use Magento\Framework\Controller\ResultFactory;

class DeleteAction extends \Magento\Framework\App\Action\Action
{
    protected $resultFactory;
    protected $_ccFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ResultFactory $resultFactory,
        CcFactory  $ccFactory
    )
    {
        $this->resultFactory = $resultFactory;
        $this->_ccFactory = $ccFactory;
        parent::__construct($context);
    }

    public function execute()
    {
       try {
            $cc_id = $this->getRequest()->getParam("cc_id");
            if ($cc_id) {
                $cardModel = $this->_ccFactory->create()->load($cc_id);
                $cardModel->delete();
                $this->messageManager->addSuccessMessage(__("Stored Payment Method was successfully removed."));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("Deletion failure. Please try again."));
        }

        return $this->_redirect('vault/cards/listaction');
    }
}