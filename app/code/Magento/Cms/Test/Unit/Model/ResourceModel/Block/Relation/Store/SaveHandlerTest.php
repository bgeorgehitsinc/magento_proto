<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cms\Test\Unit\Model\ResourceModel\Block\Relation\Store;

use Magento\Cms\Model\ResourceModel\Block;
use Magento\Cms\Model\ResourceModel\Block\Relation\Store\SaveHandler;
use Magento\Framework\Model\Entity\MetadataPool;

class SaveHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SaveHandler
     */
    protected $model;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataPool;

    /**
     * @var Block|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceBlock;

    protected function setUp()
    {
        $this->metadataPool = $this->getMockBuilder('Magento\Framework\Model\Entity\MetadataPool')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceBlock = $this->getMockBuilder('Magento\Cms\Model\ResourceModel\Block')
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new SaveHandler(
            $this->metadataPool,
            $this->resourceBlock
        );
    }

    public function testExecute()
    {
        $entityId = 1;
        $oldStore = 1;
        $newStore = 2;

        $adapter = $this->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
            ->getMockForAbstractClass();

        $whereForDelete = [
            'block_id = ?' => $entityId,
            'store_id IN (?)' => [$oldStore],
        ];
        $adapter->expects($this->once())
            ->method('delete')
            ->with('cms_block_store', $whereForDelete)
            ->willReturnSelf();

        $whereForInsert = [
            'block_id' => $entityId,
            'store_id' => $newStore,
        ];
        $adapter->expects($this->once())
            ->method('insertMultiple')
            ->with('cms_block_store', [$whereForInsert])
            ->willReturnSelf();

        $entityMetadata = $this->getMockBuilder('Magento\Framework\Model\Entity\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetadata->expects($this->once())
            ->method('getEntityConnection')
            ->willReturn($adapter);

        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with('Magento\Cms\Model\Block')
            ->willReturn($entityMetadata);

        $this->resourceBlock->expects($this->once())
            ->method('lookupStoreIds')
            ->willReturn([$oldStore]);
        $this->resourceBlock->expects($this->once())
            ->method('getTable')
            ->with('cms_block_store')
            ->willReturn('cms_block_store');

        $block = $this->getMockBuilder('Magento\Cms\Model\Block')
            ->disableOriginalConstructor()
            ->setMethods([
                'getStores',
                'getId',
            ])
            ->getMock();
        $block->expects($this->once())
            ->method('getStores')
            ->willReturn($newStore);
        $block->expects($this->exactly(3))
            ->method('getId')
            ->willReturn($entityId);

        $result = $this->model->execute('Magento\Cms\Model\Block', $block);
        $this->assertInstanceOf('Magento\Cms\Model\Block', $result);
    }
}
