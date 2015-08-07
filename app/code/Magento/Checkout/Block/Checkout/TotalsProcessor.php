<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Checkout\Block\Checkout;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Layout\AbstractTotalsProcessor;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class TotalsProcessor extends AbstractTotalsProcessor implements LayoutProcessorInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function process($jsLayout)
    {
        $totals = $jsLayout['components']['checkout']['children']['sidebar']['children']['summary']
        ['children']['totals']['children'];
        $jsLayout['components']['checkout']['children']['sidebar']['children']['summary']
        ['children']['totals']['children'] = $this->sortTotals($totals);
        return $jsLayout;
    }
}
