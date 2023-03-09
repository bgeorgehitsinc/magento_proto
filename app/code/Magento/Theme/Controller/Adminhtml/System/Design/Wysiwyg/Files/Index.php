<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Controller\Adminhtml\System\Design\Wysiwyg\Files;

use Magento\Theme\Controller\Adminhtml\System\Design\Wysiwyg\Files;

class Index extends Files
{
    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout('overlay_popup');
        $this->_view->renderLayout();
    }
}
