<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Associated Product Grid
 */
namespace Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Super\Config\Grid;

class AssociatedProduct extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return bool
     */
    public function isHasRows()
    {
        /** @var $grid \Magento\Backend\Block\Widget\Grid */
        $grid = $this->getChildBlock('grid');
        return (bool)$grid->getPreparedCollection()->getSize();
    }
}
