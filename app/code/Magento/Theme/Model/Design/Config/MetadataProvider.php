<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Model\Design\Config;

class MetadataProvider implements MetadataProviderInterface
{
    /**
     * @param array $metadata
     */
    public function __construct(
        protected readonly array $metadata
    ) {
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function get()
    {
        return $this->metadata;
    }
}
