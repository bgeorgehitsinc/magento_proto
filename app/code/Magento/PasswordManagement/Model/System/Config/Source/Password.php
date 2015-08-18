<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Source model for admin password change mode
 *
 * @codeCoverageIgnore
 */
namespace Magento\PasswordManagement\Model\System\Config\Source;

class Password extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get options for select
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('Recommended')], ['value' => 1, 'label' => __('Forced')]];
    }
}
