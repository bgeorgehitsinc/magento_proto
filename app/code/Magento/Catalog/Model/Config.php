<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model;

use Magento\Catalog\Model\Product\TypeFactory;
use Magento\Catalog\Model\ResourceModel\ConfigFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Catalog config model.
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Config extends \Magento\Eav\Model\Config
{
    const XML_PATH_LIST_DEFAULT_SORT_BY = 'catalog/frontend/default_sort_by';

    /**
     * @var mixed
     */
    private $_attributeSetsById;

    /**
     * @var mixed
     */
    private $_attributeSetsByName;

    /**
     * @var mixed
     */
    private $_attributeGroupsById;

    /**
     * @var mixed
     */
    private $_attributeGroupsByName;

    /**
     * @var mixed
     */
    private $_productTypesById;

    /**
     * @var mixed
     */
    private $_productTypesByName;

    /**
     * Array of attributes codes needed for product load
     *
     * @var array
     */
    private $_productAttributes;

    /**
     * Product Attributes used in product listing
     *
     * @var array
     */
    private $_usedInProductListing;

    /**
     * Product Attributes For Sort By
     *
     * @var array
     */
    private $_usedForSortBy;

    /**
     * @var int|float|string|null
     */
    private $_storeId = null;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Eav config
     *
     * @var \Magento\Eav\Model\Config
     */
    private $_eavConfig;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Set collection factory
     *
     * @var CollectionFactory
     */
    private $_setCollectionFactory;

    /**
     * Group collection factory
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory
     */
    private $_groupCollectionFactory;

    /**
     * Product type factory
     *
     * @var TypeFactory
     */
    private $_productTypeFactory;

    /**
     * Config factory
     *
     * @var ConfigFactory
     */
    private $_configFactory;

    /**
     * Constructor
     *
     * @param CacheInterface $cache
     * @param \Magento\Eav\Model\Entity\TypeFactory $entityTypeFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $entityTypeCollectionFactory,
     * @param StateInterface $cacheState
     * @param UniversalFactory $universalFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigFactory $configFactory
     * @param TypeFactory $productTypeFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
     * @param CollectionFactory $setCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param SerializerInterface $serializer
     * @param array $attributesForPreload
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CacheInterface $cache,
        \Magento\Eav\Model\Entity\TypeFactory $entityTypeFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $entityTypeCollectionFactory,
        StateInterface $cacheState,
        UniversalFactory $universalFactory,
        ScopeConfigInterface $scopeConfig,
        ConfigFactory $configFactory,
        TypeFactory $productTypeFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        CollectionFactory $setCollectionFactory,
        StoreManagerInterface $storeManager,
        \Magento\Eav\Model\Config $eavConfig,
        SerializerInterface $serializer = null,
        $attributesForPreload = []
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_configFactory = $configFactory;
        $this->_productTypeFactory = $productTypeFactory;
        $this->_groupCollectionFactory = $groupCollectionFactory;
        $this->_setCollectionFactory = $setCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_eavConfig = $eavConfig;

        parent::__construct(
            $cache,
            $entityTypeFactory,
            $entityTypeCollectionFactory,
            $cacheState,
            $universalFactory,
            $serializer,
            $scopeConfig,
            $attributesForPreload
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Config::class);
    }

    /**
     * Set store id
     *
     * @param integer $storeId
     * @return Config
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * Return store id, if is not set return current app store
     *
     * @return integer
     */
    public function getStoreId()
    {
        if ($this->_storeId === null) {
            return $this->_storeManager->getStore()->getId();
        }
        return $this->_storeId;
    }

    /**
     * @return $this
     */
    public function loadAttributeSets()
    {
        if ($this->_attributeSetsById) {
            return $this;
        }

        $attributeSetCollection = $this->_setCollectionFactory->create()->load();

        $this->_attributeSetsById = [];
        $this->_attributeSetsByName = [];
        foreach ($attributeSetCollection as $id => $attributeSet) {
            $entityTypeId = $attributeSet->getEntityTypeId();
            $name = $attributeSet->getAttributeSetName();
            $this->_attributeSetsById[$entityTypeId][$id] = $name;
            $this->_attributeSetsByName[$entityTypeId][strtolower($name)] = $id;
        }
        return $this;
    }

    /**
     * @param string|int|float $entityTypeId
     * @param float|int $id
     * @return false|string
     */
    public function getAttributeSetName($entityTypeId, $id)
    {
        if (!is_numeric($id)) {
            return $id;
        }
        $this->loadAttributeSets();

        if (!is_numeric($entityTypeId)) {
            $entityTypeId = $this->getEntityType($entityTypeId)->getId();
        }
        return isset(
            $this->_attributeSetsById[$entityTypeId][$id]
        ) ? $this->_attributeSetsById[$entityTypeId][$id] : false;
    }

    /**
     * @param string|int|float $entityTypeId
     * @param string|null $name
     * @return false|string|int
     */
    public function getAttributeSetId($entityTypeId, $name = null)
    {
        if (is_numeric($name)) {
            return $name;
        }
        $this->loadAttributeSets();

        if (!is_numeric($entityTypeId)) {
            $entityTypeId = $this->getEntityType($entityTypeId)->getId();
        }
        $name = strtolower($name);
        return isset(
            $this->_attributeSetsByName[$entityTypeId][$name]
        ) ? $this->_attributeSetsByName[$entityTypeId][$name] : false;
    }

    /**
     * @return $this
     */
    public function loadAttributeGroups()
    {
        if ($this->_attributeGroupsById) {
            return $this;
        }

        $attributeSetCollection = $this->_groupCollectionFactory->create()->load();

        $this->_attributeGroupsById = [];
        $this->_attributeGroupsByName = [];
        foreach ($attributeSetCollection as $id => $attributeGroup) {
            $attributeSetId = $attributeGroup->getAttributeSetId();
            $name = $attributeGroup->getAttributeGroupName();
            $this->_attributeGroupsById[$attributeSetId][$id] = $name;
            $this->_attributeGroupsByName[$attributeSetId][strtolower($name)] = $id;
        }
        return $this;
    }

    /**
     * @param float|int|string $attributeSetId
     * @param float|int|string $id
     * @return bool|string
     */
    public function getAttributeGroupName($attributeSetId, $id)
    {
        if (!is_numeric($id)) {
            return $id;
        }

        $this->loadAttributeGroups();

        if (!is_numeric($attributeSetId)) {
            $attributeSetId = $this->getAttributeSetId($attributeSetId);
        }
        return isset(
            $this->_attributeGroupsById[$attributeSetId][$id]
        ) ? $this->_attributeGroupsById[$attributeSetId][$id] : false;
    }

    /**
     * @param float|int|string $attributeSetId
     * @param string $name
     * @return bool|string|int|float
     */
    public function getAttributeGroupId($attributeSetId, $name)
    {
        if (is_numeric($name)) {
            return $name;
        }

        $this->loadAttributeGroups();

        if (!is_numeric($attributeSetId)) {
            $attributeSetId = $this->getAttributeSetId($attributeSetId);
        }
        $name = strtolower($name);
        return isset(
            $this->_attributeGroupsByName[$attributeSetId][$name]
        ) ? $this->_attributeGroupsByName[$attributeSetId][$name] : false;
    }

    /**
     * @return $this
     */
    public function loadProductTypes()
    {
        if ($this->_productTypesById) {
            return $this;
        }

        $productTypeCollection = $this->_productTypeFactory->create()->getOptionArray();

        $this->_productTypesById = [];
        $this->_productTypesByName = [];
        foreach ($productTypeCollection as $id => $type) {
            $name = $type;
            $this->_productTypesById[$id] = $name;
            $this->_productTypesByName[strtolower($name)] = $id;
        }
        return $this;
    }

    /**
     * @param string $name
     * @return false|string
     */
    public function getProductTypeId($name)
    {
        if (is_numeric($name)) {
            return $name;
        }

        $this->loadProductTypes();

        $name = strtolower($name);
        return isset($this->_productTypesByName[$name]) ? $this->_productTypesByName[$name] : false;
    }

    /**
     * @param float|int|string $id
     * @return false|string
     */
    public function getProductTypeName($id)
    {
        if (!is_numeric($id)) {
            return $id;
        }

        $this->loadProductTypes();

        return $this->_productTypesById[$id] ?? false;
    }

    /**
     * @param \Magento\Framework\DataObject $source
     * @param string $value
     * @return null|mixed
     */
    public function getSourceOptionId($source, string $value)
    {
        foreach ($source->getAllOptions() as $option) {
            if (strcasecmp($option['label'], $value) == 0 || $option['value'] == $value) {
                return $option['value'];
            }
        }
        return null;
    }

    /**
     * Load Product attributes
     *
     * @return array
     */
    public function getProductAttributes()
    {
        if ($this->_productAttributes === null) {
            $this->_productAttributes = array_keys($this->getAttributesUsedInProductListing());
        }
        return $this->_productAttributes;
    }

    /**
     * Retrieve resource model
     *
     * @return ResourceModel\Config
     */
    protected function _getResource()
    {
        return $this->_configFactory->create();
    }

    /**
     * Retrieve Attributes used in product listing
     *
     * @return array
     */
    public function getAttributesUsedInProductListing()
    {
        if ($this->_usedInProductListing === null) {
            $this->_usedInProductListing = [];
            $entityType = \Magento\Catalog\Model\Product::ENTITY;
            $attributesData = $this->_getResource()->setStoreId($this->getStoreId())->getAttributesUsedInListing();
            $this->_eavConfig->importAttributesData($entityType, $attributesData);
            foreach ($attributesData as $attributeData) {
                $attributeCode = $attributeData['attribute_code'];
                $this->_usedInProductListing[$attributeCode] = $this->_eavConfig->getAttribute(
                    $entityType,
                    $attributeCode
                );
            }
        }
        return $this->_usedInProductListing;
    }

    /**
     * Retrieve Attributes array used for sort by
     *
     * @return array
     */
    public function getAttributesUsedForSortBy()
    {
        if ($this->_usedForSortBy === null) {
            $this->_usedForSortBy = [];
            $entityType = \Magento\Catalog\Model\Product::ENTITY;
            $attributesData = $this->_getResource()->getAttributesUsedForSortBy();
            $this->_eavConfig->importAttributesData($entityType, $attributesData);
            foreach ($attributesData as $attributeData) {
                $attributeCode = $attributeData['attribute_code'];
                $this->_usedForSortBy[$attributeCode] = $this->_eavConfig->getAttribute($entityType, $attributeCode);
            }
        }
        return $this->_usedForSortBy;
    }

    /**
     * Retrieve Attributes Used for Sort by as array
     * key = code, value = name
     *
     * @return array
     */
    public function getAttributeUsedForSortByArray()
    {
        $options = ['position' => __('Position')];
        foreach ($this->getAttributesUsedForSortBy() as $attribute) {
            /* @var $attribute \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
            $options[$attribute->getAttributeCode()] = $attribute->getStoreLabel();
        }

        return $options;
    }

    /**
     * Retrieve Product List Default Sort By
     *
     * @param mixed $store
     * @return string
     */
    public function getProductListDefaultSortBy($store = null)
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_LIST_DEFAULT_SORT_BY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
