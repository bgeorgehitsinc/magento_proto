<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesRule\Controller\Adminhtml\Promo\Quote;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\SalesRule\Controller\Adminhtml\Promo\Quote;

abstract class NewHtml extends Quote implements HttpPostActionInterface
{
    /**
     * @var string
     */
    protected string $typeChecked = '';

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     * @param Date $dateFilter
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        Date $dateFilter,
        SerializerInterface $serializer
    ){
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter);

        $this->serializer   = $serializer;
    }

    /**
     * Verify class instance
     *
     * @param mixed $verifyClass
     * @return bool
     */
    public function verifyClassName($verifyClass): bool
    {
        if ($verifyClass instanceof $this->typeChecked) {
            return true;
        }

        return false;
    }

    /**
     * Get Error json
     *
     * @return bool|string
     */
    protected function getErrorJson()
    {
        return $this->serializer->serialize(
            [
                'error'     => true,
                'message'   => __('Selected type is not inherited from type %1', $this->typeChecked)
            ]
        );
    }
}