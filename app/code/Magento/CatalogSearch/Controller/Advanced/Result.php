<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogSearch\Controller\Advanced;

use Magento\CatalogSearch\Model\Advanced as ModelAdvanced;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Result extends \Magento\Framework\App\Action\Action
{

    /**
     * Catalog search advanced
     *
     * @var ModelAdvanced
     */
    protected $_catalogSearchAdvanced;

    /**
     * Construct
     *
     * @param Context $context
     * @param ModelAdvanced $catalogSearchAdvanced
     */
    public function __construct(
        Context $context,
        ModelAdvanced $catalogSearchAdvanced

    ) {
        parent::__construct($context);
        $this->_catalogSearchAdvanced = $catalogSearchAdvanced;
    }

    /**
     * Advanced Search Result page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $this->_catalogSearchAdvanced->addFilters($this->getRequest()->getQueryValue());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Catalog Advanced Search'));
        return $resultPage;
    }
}
