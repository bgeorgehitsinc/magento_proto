<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Model\ResourceModel\Design;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Config Design resource model
 */
class Config extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('design_config_grid_flat', 'entity_id');
    }
}
