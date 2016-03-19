<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Msrp\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Msrp\Model\Config as MsrpConfig;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * Class Msrp
 */
class Msrp extends AbstractModifier
{
    /**#@+
     * Field names
     */
    const FIELD_MSRP = 'msrp';
    const FIELD_MSRP_DISPLAY_ACTUAL_PRICE = 'msrp_display_actual_price_type';
    /**#@-*/

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var MsrpConfig
     */
    protected $msrpConfig;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param LocatorInterface $locator
     * @param MsrpConfig $msrpConfig
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        MsrpConfig $msrpConfig,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->msrpConfig = $msrpConfig;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $this->data = $data;
        $this->customizeMsrpFormat();

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->customizeMsrp();
        $this->customizeMsrpDisplayActualPrice();

        return $this->meta;
    }

    /**
     * Customize Msrp format
     *
     * @return $this
     */
    protected function customizeMsrpFormat()
    {
        $modelId = $this->locator->getProduct()->getId();
        if (isset($this->data[$modelId][self::DATA_SOURCE_DEFAULT][self::FIELD_MSRP])) {
            $this->data[$modelId][self::DATA_SOURCE_DEFAULT][self::FIELD_MSRP] =
                number_format($this->data[$modelId][self::DATA_SOURCE_DEFAULT][self::FIELD_MSRP], 2);
        }

        return $this;
    }

    /**
     * Customize msrp field
     *
     * @return $this
     */
    protected function customizeMsrp()
    {
        $msrpPath = $this->arrayManager->findPath(static::FIELD_MSRP, $this->meta, null, 'children');

        if ($msrpPath) {
            if ($this->msrpConfig->isEnabled()) {
                $this->meta = $this->arrayManager->merge(
                    $msrpPath . '/arguments/data/config',
                    $this->meta,
                    [
                        'addbefore' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol(),
                        'validation' => ['validate-zero-or-greater' => true],
                    ]
                );
            } else {
                $this->meta = $this->arrayManager->remove(
                    $this->arrayManager->slicePath($msrpPath, 0, -2),
                    $this->meta
                );
            }
        }

        return $this;
    }

    /**
     * Customize msrp display actual price field
     *
     * @return $this
     */
    protected function customizeMsrpDisplayActualPrice()
    {
        $msrpDisplayPath = $this->arrayManager->findPath(
            static::FIELD_MSRP_DISPLAY_ACTUAL_PRICE,
            $this->meta,
            null,
            'children'
        );

        if ($msrpDisplayPath) {
            if (!$this->msrpConfig->isEnabled()) {
                $this->meta = $this->arrayManager->remove(
                    $this->arrayManager->slicePath($msrpDisplayPath, 0, -2),
                    $this->meta
                );
            }
        }

        return $this;
    }
}
