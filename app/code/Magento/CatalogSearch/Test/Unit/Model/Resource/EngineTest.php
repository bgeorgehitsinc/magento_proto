<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Test\Unit\Model\Resource;


use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class EngineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\CatalogSearch\Model\Resource\Engine
     */
    private $target;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('\Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->setMethods(['getIfNullSql'])
            ->getMockForAbstractClass();
        $resource = $this->getMockBuilder('\Magento\Framework\App\Resource')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTableName'])
            ->getMock();
        $resource->expects($this->any())
            ->method('getConnection')
            ->with(\Magento\Framework\App\Resource::DEFAULT_WRITE_RESOURCE)
            ->will($this->returnValue($this->connection));

        $resource->expects($this->any())
            ->method('getTableName')
            ->will($this->returnArgument(0));

        $objectManager = new ObjectManager($this);
        $this->target = $objectManager->getObject(
            '\Magento\CatalogSearch\Model\Resource\Engine',
            [
                'resource' => $resource,
            ]
        );
    }

    /**
     * @param null|string $expected
     * @param array $data
     * @dataProvider prepareEntityIndexDataProvider
     */
    public function testPrepareEntityIndex($expected, array $data)
    {
        $this->assertEquals($expected, $this->target->prepareEntityIndex($data['index'], $data['separator']));
    }

    /**
     * @return array
     */
    public function prepareEntityIndexDataProvider()
    {
        return [
            [
                [],
                [
                    'index' => [],
                    'separator' => '--'
                ],
            ],
            [
                ['element1','element2','element3--element4'],
                [
                    'index' => [
                        'element1',
                        'element2',
                        [
                            'element3',
                            'element4',
                        ],
                    ],
                    'separator' => '--'
                ]
            ]
        ];
    }
}
