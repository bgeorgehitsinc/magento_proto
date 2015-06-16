<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cms\Model\Block;

use Magento\Cms\Model\Resource\Block\CollectionFactory;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magento\Cms\Model\Resource\Block\Collection
     */
    protected $collection;

    /**
     * @param \Magento\Cms\Model\Resource\Block\CollectionFactory $collectionFactory
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @return \Magento\Cms\Model\Resource\Block\Collection
     */
    protected function getCollection()
    {
        return $this->collection;
    }
}
