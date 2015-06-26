<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Resource;

interface SourceProviderInterface extends \Traversable
{
    /**
     * Returns main table name - extracted from "module/table" style and
     * validated by db adapter
     *
     * @return string
     */
    public function getMainTable();

    /**
     * Get primary key field name
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return string
     */
    public function getIdFieldName();

    /**
     * @param string $fieldName
     * @param string $alias
     * @return void
     */
    public function addFieldToSelect($fieldName, $alias);

    /**
     * Get \Magento\Framework\DB\Select instance and applies fields to select if needed
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelect();

    /**
     * Wrapper for compatibility with \Magento\Framework\Data\Collection\AbstractDb
     *
     * @param mixed $attribute
     * @param mixed $condition
     * @return $this|\Magento\Framework\Data\Collection\AbstractDb
     */
    public function addFieldToFilter($attribute, $condition = null);
}
