<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Controller\Address;

class Delete extends \Magento\Customer\Controller\Address
{
    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $addressId = $this->getRequest()->getParam('id', false);

        if ($addressId) {
            try {
                $address = $this->_addressRepository->getById($addressId);
                if ($address->getCustomerId() === $this->_getSession()->getCustomerId()) {
                    $this->_addressRepository->deleteById($addressId);
                    $this->messageManager->addSuccess(__('You deleted the address.'));
                } else {
                    $this->messageManager->addError(__('Something went wrong while deleting the address.'));
                }
            } catch (\Exception $other) {
                $this->messageManager->addException($other, __('Something went wrong while deleting the address.'));
            }
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
