<?php
/**
 * Import entity configurable product type model
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableImportExport\Model\Import\Product\Type;

use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;

class Configurable extends \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
{
    /**
     * Error codes.
     */
    const ERROR_ATTRIBUTE_CODE_IS_NOT_SUPER = 'attrCodeIsNotSuper';

    const ERROR_INVALID_PRICE_CORRECTION = 'invalidPriceCorr';

    const ERROR_INVALID_OPTION_VALUE = 'invalidOptionValue';

    const ERROR_INVALID_WEBSITE = 'invalidSuperAttrWebsite';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_ATTRIBUTE_CODE_IS_NOT_SUPER => 'Attribute with this code is not super',
        self::ERROR_INVALID_PRICE_CORRECTION => 'Super attribute price correction value is invalid',
        self::ERROR_INVALID_OPTION_VALUE => 'Invalid option value',
        self::ERROR_INVALID_WEBSITE => 'Invalid website code for super attribute',
    ];

    /**
     * Column names that holds values with particular meaning.
     *
     * @var string[]
     */
    protected $_specialAttributes = [
        '_super_products_sku',
        '_super_attribute_code',
        '_super_attribute_option',
        '_super_attribute_price_corr',
        '_super_attribute_price_website',
    ];

    /**
     * Reference array of existing product-attribute to product super attribute ID.
     *
     * Example: product_1 (underscore) attribute_id_1 => product_super_attr_id_1,
     * product_1 (underscore) attribute_id_2 => product_super_attr_id_2,
     * ...,
     * product_n (underscore) attribute_id_n => product_super_attr_id_n
     *
     * @var array
     */
    protected $_productSuperAttrs = [];

    /**
     * Array of SKU to array of super attribute values for all products.
     *
     * array (
     *     attr_set_name_1 => array(
     *         product_id_1 => array(
     *             super_attribute_code_1 => attr_value_1,
     *             ...
     *             super_attribute_code_n => attr_value_n
     *         ),
     *         ...
     *     ),
     *   ...
     * )
     *
     * @var array
     */
    protected $_skuSuperAttributeValues = [];

    /**
     * Array of SKU to array of super attributes data for validation new associated products.
     *
     * array (
     *     product_id_1 => array(
     *         super_attribute_id_1 => array(
     *             value_index_1 => TRUE,
     *             ...
     *             value_index_n => TRUE
     *         ),
     *         ...
     *     ),
     *   ...
     * )
     *
     * @var array
     */
    protected $_skuSuperData = [];

    /**
     * Super attributes codes in a form of code => TRUE array pairs.
     *
     * @var array
     */
    protected $_superAttributes = [];

    /**
     * All super attributes values combinations for each attribute set.
     *
     * @var array
     */
    protected $_superAttrValuesCombs = null;

    /**
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $_productTypesConfig;

    /**
     * @var \Magento\ImportExport\Model\Resource\Helper
     */
    protected $_resourceHelper;

    /**
     * @var \Magento\Framework\App\Resource
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected $_connection;

    /**
     * @var \Magento\Catalog\Model\Resource\Product\CollectionFactory
     */
    protected $_productColFac;

    /**
     * @var array
     */
    protected $_productData;

    /**
     * @var array
     */
    protected $_productSuperData;

    /**
     * @var array
     */
    protected $_simpleIdsToDelete;

    /**
     * @var array
     */
    protected $_superAttributesData;

    /**
     * @var null|int
     */
    protected $_nextAttrId;

    /**
     * @param \Magento\Eav\Model\Resource\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\Resource\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param \Magento\Framework\App\Resource $resource
     * @param array $params
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig
     * @param \Magento\ImportExport\Model\Resource\Helper $resourceHelper
     * @param \Magento\Catalog\Model\Resource\Product\CollectionFactory $_productColFac
     */
    public function __construct(
        \Magento\Eav\Model\Resource\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\Resource\Product\Attribute\CollectionFactory $prodAttrColFac,
        \Magento\Framework\App\Resource $resource,
        array $params,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig,
        \Magento\ImportExport\Model\Resource\Helper $resourceHelper,
        \Magento\Catalog\Model\Resource\Product\CollectionFactory $_productColFac
    ) {
        $this->_productTypesConfig = $productTypesConfig;
        $this->_resourceHelper = $resourceHelper;
        $this->_resource = $resource;
        $this->_productColFac = $_productColFac;
        parent::__construct($attrSetColFac, $prodAttrColFac, $params);
        $this->_connection = $this->_entityModel->getConnection();
    }

    /**
     * Add attribute parameters to appropriate attribute set.
     *
     * @param string $attrSetName Name of attribute set.
     * @param array $attrParams Refined attribute parameters.
     * @param mixed $attribute
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     */
    protected function _addAttributeParams($attrSetName, array $attrParams, $attribute)
    {
        // save super attributes for simplier and quicker search in future
        if ('select' == $attrParams['type'] && 1 == $attrParams['is_global']) {
            $this->_superAttributes[$attrParams['code']] = $attrParams;
        }
        return parent::_addAttributeParams($attrSetName, $attrParams, $attribute);
    }

    /**
     * Get super attribute ID (if it is not possible - return NULL).
     *
     * @param int $productId
     * @param int $attributeId
     * @return array|null
     */
    protected function _getSuperAttributeId($productId, $attributeId)
    {
        if (isset($this->_productSuperAttrs["{$productId}_{$attributeId}"])) {
            return $this->_productSuperAttrs["{$productId}_{$attributeId}"];
        } else {
            return null;
        }
    }

    /**
     * Have we check attribute for is_required? Used as last chance to disable this type of check.
     *
     * @param string $attrCode
     * @return bool
     */
    protected function _isAttributeRequiredCheckNeeded($attrCode)
    {
        // do not check super attributes
        return !$this->_isAttributeSuper($attrCode);
    }

    /**
     * Is attribute is super-attribute?
     *
     * @param string $attrCode
     * @return bool
     */
    protected function _isAttributeSuper($attrCode)
    {
        return isset($this->_superAttributes[$attrCode]);
    }

    /**
     * Validate particular attributes columns.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    protected function _isParticularAttributesValid(array $rowData, $rowNum)
    {
        if (!empty($rowData['_super_attribute_code'])) {
            $superAttrCode = $rowData['_super_attribute_code'];

            if (!$this->_isAttributeSuper($superAttrCode)) {
                // check attribute superity
                $this->_entityModel->addRowError(self::ERROR_ATTRIBUTE_CODE_IS_NOT_SUPER, $rowNum);
                return false;
            } elseif (isset($rowData['_super_attribute_option']) && strlen($rowData['_super_attribute_option'])) {
                $optionKey = strtolower($rowData['_super_attribute_option']);
                if (!isset($this->_superAttributes[$superAttrCode]['options'][$optionKey])) {
                    $this->_entityModel->addRowError(self::ERROR_INVALID_OPTION_VALUE, $rowNum);
                    return false;
                }
                // check price value
                if (!empty($rowData['_super_attribute_price_corr']) && !$this->_isPriceCorr(
                    $rowData['_super_attribute_price_corr']
                )
                ) {
                    $this->_entityModel->addRowError(self::ERROR_INVALID_PRICE_CORRECTION, $rowNum);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Array of SKU to array of super attribute values for all products.
     *
     * @param array $bunch - portion of products to process
     * @param array $newSku - imported variations list
     * @param array $oldSku - present variations list
     * @return $this
     */
    protected function _loadSkuSuperAttributeValues($bunch, $newSku, $oldSku)
    {
        if ($this->_superAttributes) {
            $attrSetIdToName = $this->_entityModel->getAttrSetIdToName();

            $productIds = [];
            foreach ($bunch as $rowData) {
                $dataWithExtraVirtualRows = $this->_parseVariations($rowData);
                if (!empty($dataWithExtraVirtualRows)) {
                    array_unshift($dataWithExtraVirtualRows, $rowData);
                } else {
                    $dataWithExtraVirtualRows = array($rowData);
                }

                foreach ($dataWithExtraVirtualRows as $data) {
                    if (!empty($data['_super_products_sku'])) {
                        if (isset($newSku[$data['_super_products_sku']])) {
                            $productIds[] = $newSku[$data['_super_products_sku']]['entity_id'];
                        } elseif (isset($oldSku[$data['_super_products_sku']])) {
                            $productIds[] = $oldSku[$data['_super_products_sku']]['entity_id'];
                        }
                    }
                }
            }

            foreach ($this->_productColFac->create()->addFieldToFilter(
                'type_id',
                $this->_productTypesConfig->getComposableTypes()
            )->addFieldToFilter(
                'entity_id',
                ['in' => $productIds]
            )->addAttributeToSelect(
                array_keys($this->_superAttributes)
            ) as $product) {
                $attrSetName = $attrSetIdToName[$product->getAttributeSetId()];

                $data = array_intersect_key($product->getData(), $this->_superAttributes);
                foreach ($data as $attrCode => $value) {
                    $attrId = $this->_superAttributes[$attrCode]['id'];
                    $this->_skuSuperAttributeValues[$attrSetName][$product->getId()][$attrId] = $value;
                }
            }
        }
        return $this;
    }

    /**
     * Array of SKU to array of super attribute values for all products.
     *
     * @return $this
     */
    protected function _loadSkuSuperData()
    {
        if (!$this->_skuSuperData) {
            $mainTable = $this->_resource->getTableName('catalog_product_super_attribute');
            $priceTable = $this->_resource->getTableName('catalog_product_super_attribute_pricing');
            $select = $this->_connection->select()->from(
                ['m' => $mainTable],
                ['product_id', 'attribute_id', 'product_super_attribute_id']
            )->joinLeft(
                ['p' => $priceTable],
                $this->_connection->quoteIdentifier(
                    'p.product_super_attribute_id'
                ) . ' = ' . $this->_connection->quoteIdentifier(
                    'm.product_super_attribute_id'
                ),
                ['value_index']
            );

            foreach ($this->_connection->fetchAll($select) as $row) {
                $attrId = $row['attribute_id'];
                $productId = $row['product_id'];
                if ($row['value_index']) {
                    $this->_skuSuperData[$productId][$attrId][$row['value_index']] = true;
                }
                $this->_productSuperAttrs["{$productId}_{$attrId}"] = $row['product_super_attribute_id'];
            }
        }
        return $this;
    }

    /**
     * Validate and prepare data about super attributes and associated products.
     *
     * @return $this
     */
    protected function _processSuperData()
    {
        if ($this->_productSuperData) {
            $usedCombs = [];
            // is associated products applicable?
            foreach (array_keys($this->_productSuperData['assoc_ids']) as $assocId) {
                if (!isset($this->_skuSuperAttributeValues[$this->_productSuperData['attr_set_code']][$assocId])) {
                    continue;
                }
                if ($this->_productSuperData['used_attributes']) {
                    $skuSuperValues = $this->_skuSuperAttributeValues[$this->_productSuperData['attr_set_code']][$assocId];
                    $usedCombParts = [];

                    foreach ($this->_productSuperData['used_attributes'] as $usedAttrId => $usedValues) {
                        if (empty($skuSuperValues[$usedAttrId]) || !isset($usedValues[$skuSuperValues[$usedAttrId]])) {
                            // invalid value or value does not exists for associated product
                            continue;
                        }
                        $usedCombParts[] = $skuSuperValues[$usedAttrId];
                        $superData['used_attributes'][$usedAttrId][$skuSuperValues[$usedAttrId]] = true;
                    }
                    $comb = implode('|', $usedCombParts);

                    if (isset($usedCombs[$comb])) {
                        // super attributes values combination was already used
                        continue;
                    }
                    $usedCombs[$comb] = true;
                }
                $this->_superAttributesData['super_link'][] = [
                    'product_id' => $assocId,
                    'parent_id' => $this->_productSuperData['product_id'],
                ];
                $this->_superAttributesData['relation'][] = [
                    'parent_id' => $this->_productSuperData['product_id'],
                    'child_id' => $assocId,
                ];
            }
            // clean up unused values pricing
            foreach ($this->_productSuperData['used_attributes'] as $usedAttrId => $usedValues) {
                foreach ($usedValues as $optionId => $isUsed) {
                    if (!$isUsed && isset($this->_superAttributesData['pricing'])) {
                        foreach ($this->_superAttributesData['pricing'] as $k => $params) {
                            if (($optionId == $params['value_index']) && ($usedAttrId == $params['product_super_attribute_id'])) {
                                unset($this->_superAttributesData['pricing'][$this->_productSuperData['product_id']][$usedAttrId][$k]);
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Parse variations string to inner format
     *
     * @param array $rowData
     *
     * @return array
     */
    protected function _parseVariations($rowData)
    {
        $prices = $this->_parseVariationPrices($rowData);
        $additionalRows = array();
        if (!isset($rowData['configurable_variations'])) {
            return $additionalRows;
        }
        $variations = explode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $rowData['configurable_variations']);
        foreach ($variations as $variation) {
            $fieldAndValuePairsText = explode($this->_entityModel->getMultipleValueSeparator(), $variation);
            $additionalRow = array();

            $fieldAndValuePairs = array();
            foreach ($fieldAndValuePairsText as $nameAndValue) {
                $nameAndValue = explode(ImportProduct::PAIR_NAME_VALUE_SEPARATOR, $nameAndValue);
                if (!empty($nameAndValue)) {
                    $value = isset($nameAndValue[1]) ? trim($nameAndValue[1]) : '';
                    $fieldName  = trim($nameAndValue[0]);
                    if ($fieldName) {
                        $fieldAndValuePairs[$fieldName] = $value;
                    }
                }
            }

            if (!empty($fieldAndValuePairs['sku'])) {
                $additionalRow['_super_products_sku'] = $fieldAndValuePairs['sku'];
                unset($fieldAndValuePairs['sku']);
                $additionalRow['display'] = isset($fieldAndValuePairs['display']) ? $fieldAndValuePairs['display'] : 1;
                unset($fieldAndValuePairs['display']);
                foreach ($fieldAndValuePairs as $attrCode => $attrValue) {
                    $additionalRow['_super_attribute_code'] = $attrCode;
                    $additionalRow['_super_attribute_option'] = $attrValue;
                    $additionalRow['_super_attribute_price_corr'] = isset($prices[$attrCode][$attrValue]) ? $prices[$attrCode][$attrValue] : '';
                    $additionalRows[] = $additionalRow;
                    $additionalRow = array();
                }
            }
        }
        return $additionalRows;
    }

    /**
     * Parse variation labels to array
     *  ...attribute_code => label ...
     *  ...attribute_code2 => label2 ...
     *
     * @param array $rowData
     *
     * @return array
     */
    protected function _parseVariationLabels($rowData)
    {
        $labels = array();
        if (!isset($rowData['configurable_variation_labels'])) {
            return $labels;
        }
        $pairFieldAndValue = explode($this->_entityModel->getMultipleValueSeparator(), $rowData['configurable_variation_labels']);

        foreach ($pairFieldAndValue as $nameAndValue) {
            $nameAndValue = explode(ImportProduct::PAIR_NAME_VALUE_SEPARATOR, $nameAndValue);
            if (!empty($nameAndValue)) {
                $value = isset($nameAndValue[1]) ? trim($nameAndValue[1]) : '';
                $attrCode  = trim($nameAndValue[0]);
                if ($attrCode) {
                    $labels[$attrCode] = $value;
                }
            }
        }
        return $labels;
    }

    /**
     * Parse variation prices to array
     *  ...[attribute_code][value] => price1 ...
     *  ...[attribute_code][value2] => price2 ...
     *
     * @param array $rowData
     *
     * @return array
     */
    protected function _parseVariationPrices($rowData)
    {
        $prices = array();
        if (!isset($rowData['configurable_variation_prices'])) {
            return $prices;
        }
        $optionRows = explode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $rowData['configurable_variation_prices']);
        foreach ($optionRows as $optionRow) {

            $pairFieldAndValue = explode($this->_entityModel->getMultipleValueSeparator(), $optionRow);

            $oneOptionValuePrice = array();
            foreach ($pairFieldAndValue as $nameAndValue) {
                $nameAndValue = explode(ImportProduct::PAIR_NAME_VALUE_SEPARATOR, $nameAndValue);
                if (!empty($nameAndValue)) {
                    $value = isset($nameAndValue[1]) ? trim($nameAndValue[1]) : '';
                    $paramName = trim($nameAndValue[0]);
                    if ($paramName) {
                        $oneOptionValuePrice[$paramName] = $value;
                    }
                }
            }

            if (!empty($oneOptionValuePrice['name']) && !empty($oneOptionValuePrice['value']) && isset($oneOptionValuePrice['price'])) {
                $prices[$oneOptionValuePrice['name']][$oneOptionValuePrice['value']] = $oneOptionValuePrice['price'];
            }
        }
        return $prices;
    }

    /**
     *  delete unnecessary links
     *
     */
    protected function _deleteData()
    {
        $linkTable = $this->_resource->getTableName('catalog_product_super_link');
        $relationTable = $this->_resource->getTableName('catalog_product_relation');

        if (($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND)
            && !empty($this->_productSuperData['product_id'])
            && !empty($this->_simpleIdsToDelete)
        ) {
            $quoted = $this->_connection->quoteInto('IN (?)', array($this->_productSuperData['product_id']));
            $quotedChildren = $this->_connection->quoteInto('IN (?)', $this->_simpleIdsToDelete);
            $this->_connection->delete($linkTable, "parent_id {$quoted} AND product_id {$quotedChildren}");
            $this->_connection->delete($relationTable, "parent_id {$quoted} AND child_id {$quotedChildren}");
        }
    }

    /**
     *  collected link data insertion
     *
     */
    protected function _insertData()
    {
        $mainTable = $this->_resource->getTableName('catalog_product_super_attribute');
        $labelTable = $this->_resource->getTableName('catalog_product_super_attribute_label');
        $priceTable = $this->_resource->getTableName('catalog_product_super_attribute_pricing');
        $linkTable = $this->_resource->getTableName('catalog_product_super_link');
        $relationTable = $this->_resource->getTableName('catalog_product_relation');

        $mainData = [];
        foreach ($this->_superAttributesData['attributes'] as $productId => $attributesData) {
            foreach ($attributesData as $attrId => $row) {
                $row['product_id'] = $productId;
                $row['attribute_id'] = $attrId;
                $mainData[] = $row;
            }
        }
        if ($mainData) {
            $this->_connection->insertOnDuplicate($mainTable, $mainData);
        }
        if ($this->_superAttributesData['labels']) {
            $this->_connection->insertOnDuplicate($labelTable, $this->_superAttributesData['labels']);
        }
        if ($this->_superAttributesData['pricing']) {
            $this->_connection->insertOnDuplicate(
                $priceTable,
                $this->_superAttributesData['pricing'],
                ['is_percent', 'pricing_value']
            );
        }
        if ($this->_superAttributesData['super_link']) {
            $this->_connection->insertOnDuplicate($linkTable, $this->_superAttributesData['super_link']);
        }
        if ($this->_superAttributesData['relation']) {
            $this->_connection->insertOnDuplicate($relationTable, $this->_superAttributesData['relation']);
        }
    }

    /**
     *  get New supper attribute id
     *
     * @return int
     */
    protected function _getNextAttrId()
    {
        if (!$this->_nextAttrId) {
            $mainTable = $this->_resource->getTableName('catalog_product_super_attribute');
            $this->_nextAttrId = $this->_resourceHelper->getNextAutoincrement($mainTable);
        }
        $this->_nextAttrId++;
        return $this->_nextAttrId;
    }

    /**
     *  collect super data
     *
     * @param array $rowData
     * @param int $rowNum
     *
     */
    protected function _collectSuperData($rowData, $rowNum)
    {
        $productId = $this->_productData['entity_id'];

        $this->_processSuperData();

        $this->_productSuperData = [
            'product_id' => $productId,
            'attr_set_code' => $this->_productData['attr_set_code'],
            'used_attributes' => empty($this->_skuSuperData[$productId]) ? [] : $this
                ->_skuSuperData[$productId],
            'assoc_ids' => [],
        ];

        $additionalRows = $this->_parseVariations($rowData);
        $variationLabels = $this->_parseVariationLabels($rowData);

        foreach ($additionalRows as $data) {
            $this->_collectAssocIds($data);

            if (!isset($this->_superAttributes[$data['_super_attribute_code']])) {
                continue;
            }
            $attrParams = $this->_superAttributes[$data['_super_attribute_code']];

            if ($this->_getSuperAttributeId($productId, $attrParams['id'])) {
                $productSuperAttrId = $this->_getSuperAttributeId($productId, $attrParams['id']);
            } elseif (isset($this->_superAttributesData['attributes'][$productId][$attrParams['id']])) {
                $productSuperAttrId = $this->_superAttributesData['attributes'][$productId][$attrParams['id']]['product_super_attribute_id'];
                $this->_collectSuperDataLabels($data, $productSuperAttrId, $productId, $variationLabels);
            } else {
                $productSuperAttrId = $this->_getNextAttrId();
                $this->_collectSuperDataLabels($data, $productSuperAttrId, $productId, $variationLabels);
            }

            if ($productSuperAttrId) {
                $this->_collectSuperDataPrice($data, $productSuperAttrId);
            }
        }
    }

    /**
     *  collect super data price
     *
     * @param array $data
     * @param int $productSuperAttrId
     *
     */
    protected function _collectSuperDataPrice($data, $productSuperAttrId)
    {
        $attrParams = $this->_superAttributes[$data['_super_attribute_code']];
        if (isset($data['_super_attribute_option']) && strlen($data['_super_attribute_option'])) {
            $optionId = $attrParams['options'][strtolower($data['_super_attribute_option'])];

            if (!isset($this->_productSuperData['used_attributes'][$attrParams['id']][$optionId])) {
                $this->_productSuperData['used_attributes'][$attrParams['id']][$optionId] = false;
            }
            if (!empty($data['_super_attribute_price_corr'])) {
                $this->_superAttributesData['pricing'][] = [
                    'product_super_attribute_id' => $productSuperAttrId,
                    'value_index' => $optionId,
                    'is_percent' => '%' == substr($data['_super_attribute_price_corr'], -1),
                    'pricing_value' => (double)rtrim($data['_super_attribute_price_corr'], '%'),
                    'website_id' => 0,
                ];
            }
        }
    }

    /**
     *  collect assoc ids and simpleIds to break links
     *
     * @param array $data
     *
     */
    protected function _collectAssocIds($data)
    {
        $newSku = $this->_entityModel->getNewSku();
        $oldSku = $this->_entityModel->getOldSku();
        if (!empty($data['_super_products_sku'])) {
            $superProductId = '';
            if (isset($newSku[$data['_super_products_sku']])) {
                $superProductId = $newSku[$data['_super_products_sku']]['entity_id'];
            } elseif (isset($oldSku[$data['_super_products_sku']])) {
                $superProductId = $oldSku[$data['_super_products_sku']]['entity_id'];
            }

            if ($superProductId) {
                if (isset($data['display']) && $data['display'] == 0) {
                    $this->_simpleIdsToDelete[] = $superProductId;
                } else {
                    $this->_productSuperData['assoc_ids'][$superProductId] = true;
                }
            }
        }
    }

    /**
     *  collect super data price
     *
     * @param array $data
     * @param int $productSuperAttrId
     * @param int $productId
     * @param array $variationLabels
     *
     */
    protected function _collectSuperDataLabels($data, $productSuperAttrId, $productId, $variationLabels)
    {
        $attrParams = $this->_superAttributes[$data['_super_attribute_code']];
        $this->_superAttributesData['attributes'][$productId][$attrParams['id']] = [
            'product_super_attribute_id' => $productSuperAttrId,
            'position' => 0,
        ];
        $label = isset($variationLabels[$data['_super_attribute_code']]) ? $variationLabels[$data['_super_attribute_code']] : $attrParams['frontend_label'];
        $this->_superAttributesData['labels'][] = [
            'product_super_attribute_id' => $productSuperAttrId,
            'store_id' => 0,
            'use_default' => $label ? 0 : 1,
            'value' => $label,
        ];
    }

    /**
     * Save product type specific data.
     *
     * @throws \Exception
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     */
    public function saveData()
    {
        $newSku = $this->_entityModel->getNewSku();
        $oldSku = $this->_entityModel->getOldSku();
        $this->_productSuperData = [];
        $this->_productData = null;

        if ($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND) {
            $this->_loadSkuSuperData();
        }

        while ($bunch = $this->_entityModel->getNextBunch()) {
            $this->_superAttributesData = [
                'attributes' => [],
                'labels' => [],
                'pricing' => [],
                'super_link' => [],
                'relation' => [],
            ];

            $this->_simpleIdsToDelete = array();

            $this->_loadSkuSuperAttributeValues($bunch, $newSku, $oldSku);

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                // remember SCOPE_DEFAULT row data
                $scope = $this->_entityModel->getRowScope($rowData);
                if ((\Magento\CatalogImportExport\Model\Import\Product::SCOPE_DEFAULT == $scope) && !empty($rowData[\Magento\CatalogImportExport\Model\Import\Product::COL_SKU])) {

                    $this->_productData = isset($newSku[$rowData[ImportProduct::COL_SKU]]) ? $newSku[$rowData[ImportProduct::COL_SKU]] : $oldSku[$rowData[ImportProduct::COL_SKU]];

                    if ($this->_type != $this->_productData['type_id']) {
                        $this->_productData = null;
                        continue;
                    }
                    $this->_collectSuperData($rowData, $rowNum);
                }
            }

            // save last product super data
            $this->_processSuperData();

            $this->_deleteData();

            $this->_insertData();
        }
        return $this;
    }

    /**
     * Validate row attributes. Pass VALID row data ONLY as argument.
     *
     * @param array $rowData
     * @param int $rowNum
     * @param bool $isNewProduct Optional
     * @return bool
     */
    public function isRowValid(array $rowData, $rowNum, $isNewProduct = true)
    {
        $error = false;
        $dataWithExtraVirtualRows = $this->_parseVariations($rowData);
        if (!empty($dataWithExtraVirtualRows)) {
            array_unshift($dataWithExtraVirtualRows, $rowData);
        } else {
            $dataWithExtraVirtualRows = array($rowData);
        }
        foreach ($dataWithExtraVirtualRows as $data) {
            $error |= !parent::isRowValid($data, $rowNum, $isNewProduct);
        }
        return !$error;
    }
}
