<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Newsletter problems collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Newsletter\Model\ResourceModel\Grid;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends \Magento\Newsletter\Model\ResourceModel\Problem\Collection
{
    /**
     * Adds queue info to grid
     *
     * @return AbstractCollection|\Magento\Newsletter\Model\ResourceModel\Grid\Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addSubscriberInfo()->addQueueInfo();
        return $this;
    }
}
