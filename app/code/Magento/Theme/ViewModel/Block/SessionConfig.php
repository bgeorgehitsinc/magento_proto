<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Theme\ViewModel\Block;

use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Provide cookie configuration
 */
class SessionConfig implements ArgumentInterface
{
    /**
     * Constructor
     *
     * @param ConfigInterface $sessionConfig
     */
    public function __construct(
        private readonly ConfigInterface $sessionConfig
    ) {
    }
    /**
     * Get session.cookie_secure
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getCookieSecure(): bool
    {
        return $this->sessionConfig->getCookieSecure();
    }
}
