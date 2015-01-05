<?php
/**
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */
namespace Magento\Framework\Module\Dir;

class ReverseResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Module\Dir\ReverseResolver
     */
    protected $_model;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\Module\Dir|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_moduleDirs;

    protected function setUp()
    {
        $this->_moduleList = $this->getMock('Magento\Framework\Module\ModuleListInterface');
        $this->_moduleDirs = $this->getMock('Magento\Framework\Module\Dir', [], [], '', false, false);
        $this->_model = new \Magento\Framework\Module\Dir\ReverseResolver($this->_moduleList, $this->_moduleDirs);
    }

    /**
     * @param string $path
     * @param string $expectedResult
     * @dataProvider getModuleNameDataProvider
     */
    public function testGetModuleName($path, $expectedResult)
    {
        $this->_moduleList->expects($this->once())->method('getNames')->will(
            $this->returnValue(['Fixture_ModuleOne', 'Fixture_ModuleTwo'])
        );
        $this->_moduleDirs->expects(
            $this->atLeastOnce()
        )->method(
            'getDir'
        )->will(
            $this->returnValueMap(
                [
                    ['Fixture_ModuleOne', '', 'vendor/magento/Fixture/ModuleOne'],
                    ['Fixture_ModuleTwo', '', 'vendor/magento/Fixture/ModuleTwo'],
                ]
            )
        );
        $this->assertSame($expectedResult, $this->_model->getModuleName($path));
    }

    public function getModuleNameDataProvider()
    {
        return [
            'module root dir' => ['vendor/magento/Fixture/ModuleOne', 'Fixture_ModuleOne'],
            'module root dir trailing slash' => ['vendor/magento/Fixture/ModuleOne/', 'Fixture_ModuleOne'],
            'module root dir backward slash' => ['vendor/magento\\Fixture\\ModuleOne', 'Fixture_ModuleOne'],
            'dir in module' => ['vendor/magento/Fixture/ModuleTwo/etc', 'Fixture_ModuleTwo'],
            'dir in module trailing slash' => ['vendor/magento/Fixture/ModuleTwo/etc/', 'Fixture_ModuleTwo'],
            'dir in module backward slash' => ['vendor/magento/Fixture/ModuleTwo\\etc', 'Fixture_ModuleTwo'],
            'file in module' => ['vendor/magento/Fixture/ModuleOne/etc/config.xml', 'Fixture_ModuleOne'],
            'file in module backward slash' => [
                'vendor\\magento\\Fixture\\ModuleOne\\etc\\config.xml',
                'Fixture_ModuleOne',
            ],
            'unknown module' => ['vendor/magento/Unknown/Module', null]
        ];
    }
}
