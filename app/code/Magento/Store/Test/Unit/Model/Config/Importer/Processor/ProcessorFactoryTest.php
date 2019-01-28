<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Store\Test\Unit\Model\Config\Importer\Processor;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Config\Importer\Processor\Create;
use Magento\Store\Model\Config\Importer\Processor\Delete;
use Magento\Store\Model\Config\Importer\Processor\ProcessorFactory;
use Magento\Store\Model\Config\Importer\Processor\ProcessorInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProcessorFactory
     */
    private $model;

    /**
     * @var ObjectManagerInterface|Mock
     */
    private $objectManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMockForAbstractClass();

        $this->model = new ProcessorFactory(
            $this->objectManagerMock,
            [
                ProcessorFactory::TYPE_CREATE => Create::class,
                ProcessorFactory::TYPE_DELETE => Delete::class,
                'wrongType' => \stdClass::class,
            ]
        );
    }

    public function testCreate()
    {
        $processorMock = $this->getMockBuilder(ProcessorInterface::class)
            ->getMockForAbstractClass();
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(Create::class)
            ->willReturn($processorMock);

        $this->assertInstanceOf(
            ProcessorInterface::class,
            $this->model->create(ProcessorFactory::TYPE_CREATE)
        );
    }

    /**
     */
    public function testCreateNonExisted()
    {
        $this->setExpectedException(\Magento\Framework\Exception\ConfigurationMismatchException::class, 'The class for "dummyType" type wasn\'t declared. Enter the class and try again.');

        $this->model->create('dummyType');
    }

    /**
     */
    public function testCreateWrongImplementation()
    {
        $this->setExpectedException(\Magento\Framework\Exception\ConfigurationMismatchException::class, 'stdClass should implement');

        $type = 'wrongType';
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(\stdClass::class)
            ->willReturn(new \stdClass());

        $this->model->create($type);
    }
}
