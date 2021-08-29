<?php
/**
 * PageCache controller
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PageCache\Controller;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Layout\LayoutCacheKeyInterface;
use Psr\Log\LoggerInterface;

abstract class Block extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Translate\InlineInterface
     */
    protected $translateInline;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Base64Json
     */
    private $base64jsonSerializer;

    /**
     * Layout cache keys to be able to generate different cache id for same handles
     *
     * @var LayoutCacheKeyInterface
     */
    private $layoutCacheKey;

    /**
     * @var string
     */
    private $layoutCacheKeyName = 'mage_pagecache';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Block constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param LoggerInterface $logger
     * @param Json|null $jsonSerializer
     * @param Base64Json|null $base64jsonSerializer
     * @param LayoutCacheKeyInterface|null $layoutCacheKey
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        LoggerInterface $logger = null,
        Json $jsonSerializer = null,
        Base64Json $base64jsonSerializer = null,
        LayoutCacheKeyInterface $layoutCacheKey = null
    ) {
        parent::__construct($context);
        $this->translateInline = $translateInline;
        $this->logger = $logger
            ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->jsonSerializer = $jsonSerializer
            ?: ObjectManager::getInstance()->get(Json::class);
        $this->base64jsonSerializer = $base64jsonSerializer
            ?: ObjectManager::getInstance()->get(Base64Json::class);
        $this->layoutCacheKey = $layoutCacheKey
            ?: ObjectManager::getInstance()->get(LayoutCacheKeyInterface::class);
    }

    /**
     * Get blocks from layout by handles
     *
     * @return array [\Element\BlockInterface]
     */
    protected function _getBlocks()
    {
        $blocks = $this->getRequest()->getParam('blocks', '');
        $handles = $this->getRequest()->getParam('handles', '');

        if (!$handles || !$blocks) {
            return [];
        }
        $blocks = $this->unserialize($blocks);
        $handles = $this->base64jsonSerializer->unserialize($handles);

        $layout = $this->_view->getLayout();
        $this->layoutCacheKey->addCacheKeys($this->layoutCacheKeyName);

        $this->_view->loadLayout($handles, true, true, false);
        $data = [];

        foreach ($blocks as $blockName) {
            $blockInstance = $layout->getBlock($blockName);
            if (is_object($blockInstance)) {
                $data[$blockName] = $blockInstance;
            }
        }

        return $data;
    }

    /**
     * Unserialize JSON string
     *
     * @param string $string
     * @return array|null
     */
    protected function unserialize($string)
    {
        return $this->jsonSerializer->unserialize($string);
    }
}
