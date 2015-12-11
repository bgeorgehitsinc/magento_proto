<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\App\Response\HeaderProvider;

use \Magento\Framework\App\Response\HeaderProvider\AbstractHeader;
use \Magento\Store\Model\Store;

/**
 * Adds an Strict-Transport-Security (HSTS) header to HTTP responses.
 */
class Hsts extends AbstractHeader
{
    /**
     * Enable HSTS config path
     */
    const XML_PATH_ENABLE_HSTS = 'web/secure/enable_hsts';

    /**
     * Strict-Transport-Security (HSTS) Header name
     *
     * @var string
     */
    protected $name = 'Strict-Transport-Security';

    /**
     * Strict-Transport-Security (HSTS) header value
     *
     * @var string
     */
    protected $value = 'max-age=31536000';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function canApply()
    {
        return (bool)$this->scopeConfig->isSetFlag(Store::XML_PATH_SECURE_IN_FRONTEND)
            && $this->scopeConfig->isSetFlag(Store::XML_PATH_SECURE_IN_ADMINHTML)
            && $this->scopeConfig->isSetFlag($this::XML_PATH_ENABLE_HSTS);
    }
}
