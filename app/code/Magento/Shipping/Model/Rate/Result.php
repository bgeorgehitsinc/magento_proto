<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Shipping\Model\Rate;

use Magento\Framework\Filter\Sprintf;
use Magento\Quote\Model\Quote\Address\RateResult\AbstractResult;
use Magento\Quote\Model\Quote\Address\RateResult\Error as RateResultError;
use Magento\Quote\Model\Quote\Address\RateResult\Method as RateResultMethod;
use Magento\Shipping\Model\Rate\Result as RateResult;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Result
 *
 * Container for Rates
 *
 * @api
 * @since 100.0.2
 */
class Result
{
    /**
     * Shipping method rates
     *
     * @var AbstractResult[]
     */
    protected $_rates = [];

    /**
     * Shipping errors
     *
     * @var null|bool
     */
    protected $_error = null;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
    }

    /**
     * Reset result
     *
     * @return $this
     */
    public function reset()
    {
        $this->_rates = [];
        return $this;
    }

    /**
     * Set Error
     *
     * @param bool $error
     * @return void
     */
    public function setError($error)
    {
        $this->_error = $error;
    }

    /**
     * Get Error
     *
     * @return null|bool
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Add a rate to the result
     *
     * @param AbstractResult|RateResult $result
     * @return $this
     */
    public function append($result)
    {
        if ($result instanceof RateResultError) {
            $this->setError(true);
        }
        if ($result instanceof AbstractResult) {
            $this->_rates[] = $result;
        } elseif ($result instanceof RateResult) {
            $rates = $result->getAllRates();
            foreach ($rates as $rate) {
                $this->append($rate);
            }
        }
        return $this;
    }

    /**
     * Return all quotes in the result
     *
     * @return RateResultMethod[]
     */
    public function getAllRates()
    {
        return $this->_rates;
    }

    /**
     * Return rate by id in array
     *
     * @param int $id
     * @return RateResultMethod|null
     */
    public function getRateById($id)
    {
        return isset($this->_rates[$id]) ? $this->_rates[$id] : null;
    }

    /**
     * Return quotes for specified type
     *
     * @param string $carrier
     * @return array
     */
    public function getRatesByCarrier($carrier)
    {
        $result = [];
        foreach ($this->_rates as $rate) {
            if ($rate->getCarrier() === $carrier) {
                $result[] = $rate;
            }
        }
        return $result;
    }

    /**
     * Converts object to array
     *
     * @return array
     */
    public function asArray()
    {
        if ($this->_storeManager->getStore()->getBaseCurrency()
            && $this->_storeManager->getStore()->getCurrentCurrency()
        ) {
            $currencyFilter = $this->_storeManager->getStore()->getCurrentCurrency()->getFilter();
            $currencyFilter->setRate(
                $this->_storeManager->getStore()->getBaseCurrency()->getRate(
                    $this->_storeManager->getStore()->getCurrentCurrency()
                )
            );
        } elseif ($this->_storeManager->getStore()->getDefaultCurrency()) {
            $currencyFilter = $this->_storeManager->getStore()->getDefaultCurrency()->getFilter();
        } else {
            $currencyFilter = new Sprintf('%s', 2);
        }
        $rates = [];
        $allRates = $this->getAllRates();
        foreach ($allRates as $rate) {
            $rates[$rate->getCarrier()]['title'] = $rate->getCarrierTitle();
            $rates[$rate->getCarrier()]['methods'][$rate->getMethod()] = [
                'title' => $rate->getMethodTitle(),
                'price' => $rate->getPrice(),
                'price_formatted' => $currencyFilter->filter($rate->getPrice()),
            ];
        }
        return $rates;
    }

    /**
     * Get cheapest rate
     *
     * @return null|RateResultMethod
     */
    public function getCheapestRate()
    {
        $cheapest = null;
        $minPrice = 100000;
        foreach ($this->getAllRates() as $rate) {
            if (is_numeric($rate->getPrice()) && $rate->getPrice() < $minPrice) {
                $cheapest = $rate;
                $minPrice = $rate->getPrice();
            }
        }
        return $cheapest;
    }

    /**
     * Sort rates by price from min to max
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function sortRatesByPrice()
    {
        if (!is_array($this->_rates) || !count($this->_rates)) {
            return $this;
        }
        /* @var RateResultMethod $rate */
        foreach ($this->_rates as $i => $rate) {
            $tmp[$i] = $rate->getPrice();
        }

        natsort($tmp);

        foreach ($tmp as $i => $price) {
            $result[] = $this->_rates[$i];
        }

        $this->reset();
        $this->_rates = $result;
        return $this;
    }

    /**
     * Set price for each rate according to count of packages
     *
     * @param int $packageCount
     * @return $this
     */
    public function updateRatePrice($packageCount)
    {
        if ($packageCount > 1) {
            foreach ($this->_rates as $rate) {
                $rate->setPrice($rate->getPrice() * $packageCount);
            }
        }

        return $this;
    }
}
