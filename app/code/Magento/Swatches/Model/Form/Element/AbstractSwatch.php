<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Swatches\Model\Form\Element;

class AbstractSwatch extends \Magento\Framework\Data\Form\Element\Select
{
    /**
     * Get swatch values
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getValues()
    {
        $options = [];
        $attribute = $this->getData('entity_attribute');
        if ($attribute instanceof \Magento\Catalog\Model\Resource\Eav\Attribute) {
            $options = $attribute->getSource()->getAllOptions(true, true);
        }
        return $options;
    }
}
