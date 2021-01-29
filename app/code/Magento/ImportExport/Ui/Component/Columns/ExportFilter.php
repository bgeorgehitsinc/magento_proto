<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportExport\Ui\Component\Columns;

use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\ImportExport\Model\Export\AttributeFilterType;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ExportFilter extends Column
{
    /**
     * @var AttributeFilterType
     */
    private $attributeFilterType;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param AttributeFilterType $attributeFilterType
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        AttributeFilterType $attributeFilterType,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->attributeFilterType = $attributeFilterType;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource) :array
    {
        if (!empty($dataSource['data']['items'])) {
            /** @var AbstractDataProvider $dataProvider */
            $dataProvider = $this->getContext()->getDataProvider();
            $collection = $dataProvider->getCollection();

            foreach ($dataSource['data']['items'] as &$item) {
                /** @var Attribute $attribute */
                $attribute = $collection->getItemById($item['attribute_id']);

                try {
                    $filter = [
                        'type' => $this->attributeFilterType->getAttributeFilterType($attribute)
                    ];

                    if ($attribute->usesSource()) {
                        $options = $attribute->getSource()->getAllOptions();
                        $filter['options'] = array_filter($options, function ($option) {
                            return $option['value'] !== '';
                        });
                        $filter['options'] = array_values($filter['options']);

                        if (empty($filter['options'])) {
                            throw new LocalizedException(
                                __('We can\'t filter an attribute with no attribute options.')
                            );
                        }
                    }
                } catch (LocalizedException $e) {
                    $filter = $e->getMessage();
                }

                $item[$this->getName()] = $filter;
            }
        }

        return $dataSource;
    }
}
