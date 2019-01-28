<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Model\Indexer\Product\Price\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RowsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Action\Rows
     */
    protected $_model;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_model = $objectManager->getObject(\Magento\Catalog\Model\Indexer\Product\Price\Action\Rows::class);
    }

    /**
     */
    public function testEmptyIds()
    {
        $this->setExpectedException(\Magento\Framework\Exception\InputException::class, 'Bad value was supplied.');

        $this->_model->execute(null);
    }
}
