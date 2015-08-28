<?php
/**
 * List of suggested attributes
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableProduct\Model;

class SuggestedAttributeList
{
    /**
     * @var ConfigurableAttributeHandler
     */
    protected $configurableAttributeHandler;

    /**
     * Catalog resource helper
     *
     * @var \Magento\Catalog\Model\Resource\Helper
     */
    protected $_resourceHelper;
    /**
     * @param ConfigurableAttributeHandler $configurableAttributeHandler
     * @param \Magento\Catalog\Model\Resource\Helper $resourceHelper
     */
    public function __construct(
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler,
        \Magento\Catalog\Model\Resource\Helper $resourceHelper
    ) {
        $this->configurableAttributeHandler = $configurableAttributeHandler;
        $this->_resourceHelper = $resourceHelper;
    }
    /**
     * Retrieve list of attributes with admin store label containing $labelPart
     *
     * @param string $labelPart
     * @return \Magento\Catalog\Model\Resource\Product\Attribute\Collection
     */
    public function getSuggestedAttributes($labelPart)
    {
        $escapedLabelPart = $this->_resourceHelper->addLikeEscape($labelPart, ['position' => 'any']);
        /** @var $collection \Magento\Catalog\Model\Resource\Product\Attribute\Collection */
        $collection = $this->configurableAttributeHandler->getApplicableAttributes()->addFieldToFilter(
            'frontend_label',
            ['like' => $escapedLabelPart]
        );
        $result = [];
        foreach ($collection->getItems() as $id => $attribute) {
            /** @var $attribute \Magento\Catalog\Model\Resource\Eav\Attribute */
            if ($this->configurableAttributeHandler->isAttributeApplicable($attribute)) {
                $result[$id] = [
                    'id' => $attribute->getId(),
                    'label' => $attribute->getFrontendLabel(),
                    'code' => $attribute->getAttributeCode(),
                    'options' => $attribute->getSource()->getAllOptions(false),
                ];
            }
        }
        return $result;
    }
}
