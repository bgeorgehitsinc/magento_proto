<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StoreGraphQl\Plugin;

use Magento\Store\Model\Website as ModelWebsite;
use Magento\StoreGraphQl\Model\Resolver\Store\ConfigIdentity;

/**
 * Website plugin to provide identities for cache invalidation
 */
class Website
{
    /**
     * Add graphql store config tag to the website cache identities.
     *
     * @param ModelWebsite $subject
     * @param array $result
     * @return array
     */
    public function afterGetIdentities(ModelWebsite $subject, array $result): array
    {
        $storeIds = $subject->getStoreIds();
        foreach ($storeIds as $storeId) {
            $result[] = sprintf('%s_%s', ConfigIdentity::CACHE_TAG, $storeId);
        }
        return $result;
    }
}
