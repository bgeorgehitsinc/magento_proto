<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Reflection;

use Magento\Framework\Dto\DtoProcessor;
use Magento\Framework\Exception\SerializationException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Reflection\Mock\TestDataInterface;
use Magento\Framework\Reflection\Mock\TestDataObject;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class DataObjectProcessorTest extends TestCase
{
    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DtoProcessor
     */
    private $dtoProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->dataObjectProcessor = $this->objectManager->get(DataObjectProcessor::class);
        $this->dtoProcessor = $this->objectManager->get(DtoProcessor::class);
    }

    /**
     * @param array $expectedOutputDataArray
     *
     * @throws SerializationException
     * @throws ReflectionException
     * @dataProvider buildOutputDataArrayDataProvider
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testBuildOutputDataArray(array $expectedOutputDataArray): void
    {
        /** @var TestDataObject $testDataObject */
        $testDataObject = $this->dtoProcessor->createFromArray($expectedOutputDataArray, TestDataObject::class);

        $outputData = $this->dataObjectProcessor
            ->buildOutputDataArray($testDataObject, TestDataInterface::class);

        $this->assertEquals($expectedOutputDataArray, $outputData);
    }

    /**
     * @return array
     */
    public function buildOutputDataArrayDataProvider(): array
    {
        $expectedOutputDataArray = [
            'id' => '1',
            'address' => 'someAddress',
            'default_shipping' => 'true',
            'required_billing' => 'false',
        ];

        $extensionAttributeArray = [
            'attribute1' => 'value1',
            'attribute2' => 'value2'
        ];

        return [
            'NoExtensionAttributes' => [$expectedOutputDataArray],
            'WithExtensionAttributes' => [
                array_merge(
                    $expectedOutputDataArray,
                    ['extension_attributes' => $extensionAttributeArray]
                )
            ]
        ];
    }
}
