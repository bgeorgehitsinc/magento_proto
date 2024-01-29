<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StoreGraphQl\Model\Context;

use Magento\GraphQl\Model\Query\ContextParametersInterface;
use Magento\GraphQl\Model\Query\ContextParametersProcessorInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @inheritdoc
 */
class AddStoreInfoToContext implements ContextParametersProcessorInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(ContextParametersInterface $contextParameters): ContextParametersInterface
    {
        $currentStore = $this->storeManager->getStore();
        $contextParameters->addExtensionAttribute('store', $currentStore);

        return $contextParameters;
    }
}
