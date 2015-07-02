<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Model\Resource\Order\Invoice;

use Magento\Sales\Model\Resource\EntityAbstract;
use Magento\Framework\Model\Resource\Db\VersionControl\Snapshot;
use Magento\Sales\Model\Spi\InvoiceCommentResourceInterface;

/**
 * Flat sales order invoice comment resource
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Comment extends EntityAbstract implements InvoiceCommentResourceInterface
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'sales_order_invoice_comment_resource';

    /**
     * Validator
     *
     * @var \Magento\Sales\Model\Order\Invoice\Comment\Validator
     */
    protected $validator;

    /**
     * @param \Magento\Framework\Model\Resource\Db\Context $context
     * @param \Magento\Sales\Model\Resource\Attribute $attribute
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param Snapshot $entitySnapshot
     * @param \Magento\Framework\Model\Resource\Db\VersionControl\RelationComposite $entityRelationComposite
     * @param \Magento\Sales\Model\Order\Invoice\Comment\Validator $validator
     * @param string $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\Resource\Db\Context $context,
        Snapshot $entitySnapshot,
        \Magento\Framework\Model\Resource\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\Sales\Model\Resource\Attribute $attribute,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        \Magento\Sales\Model\Order\Invoice\Comment\Validator $validator,
        $resourcePrefix = null
    ) {
        $this->validator = $validator;
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $attribute,
            $sequenceManager,
            $resourcePrefix
        );
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_invoice_comment', 'entity_id');
    }

    /**
     * Performs validation before save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Magento\Sales\Model\Order\Invoice\Comment $object */
        if (!$object->getParentId() && $object->getInvoice()) {
            $object->setParentId($object->getInvoice()->getId());
        }

        parent::_beforeSave($object);
        $errors = $this->validator->validate($object);
        if (!empty($errors)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Cannot save comment:\n%1", implode("\n", $errors))
            );
        }

        return $this;
    }
}
