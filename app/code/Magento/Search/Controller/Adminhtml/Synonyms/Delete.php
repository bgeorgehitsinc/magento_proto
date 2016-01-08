<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Search\Controller\Adminhtml\Synonyms;

/**
 * Delete Controller
 */
class Delete extends \Magento\Backend\App\Action
{
    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('group_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->_objectManager->create('Magento\Search\Model\SynonymGroup');
                $model->load($id);
                $repository = $this->_objectManager->create('Magento\Search\Api\SynonymGroupRepositoryInterface');
                $repository->delete($model);
                $this->messageManager->addSuccess(__('The synonyms group has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->messageManager->addError(__('We can\'t find a synonum group to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
