<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an e-mail
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Catalog
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
/** @var $installer \Magento\Catalog\Model\Resource\Setup */

$installer->updateAttribute(
    \Magento\Catalog\Model\Product::ENTITY,
    'msrp_enabled',
    'source_model',
    'Magento\Catalog\Model\Product\Attribute\Source\Msrp\Type\Enabled'
);

$installer->updateAttribute(
    \Magento\Catalog\Model\Product::ENTITY,
    'msrp_enabled',
    'default_value',
    \Magento\Catalog\Model\Product\Attribute\Source\Msrp\Type\Enabled::MSRP_ENABLE_USE_CONFIG
);

$installer->updateAttribute(
    \Magento\Catalog\Model\Product::ENTITY,
    'msrp_display_actual_price_type',
    'source_model',
    'Magento\Catalog\Model\Product\Attribute\Source\Msrp\Type\Price'
);

$installer->updateAttribute(
    \Magento\Catalog\Model\Product::ENTITY,
    'msrp_display_actual_price_type',
    'default_value',
    \Magento\Catalog\Model\Product\Attribute\Source\Msrp\Type\Price::TYPE_USE_CONFIG
);
