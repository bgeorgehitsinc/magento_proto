<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Test\Unit\Model\Theme;

use Magento\Framework\App\Area;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Theme\Model\Theme\ThemeProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class ThemeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new ObjectManagerHelper($this);
    }

    public function testGetByFullPath()
    {
        $path = 'frontend/Magento/luma';
        $collectionFactory = $this->getMock(
            'Magento\Theme\Model\ResourceModel\Theme\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );
        $collectionMock = $this->getMock('Magento\Theme\Model\ResourceModel\Theme\Collection', [], [], '', false);
        $theme = $this->getMock('Magento\Framework\View\Design\ThemeInterface', [], [], '', false);
        $collectionMock->expects(
            $this->once()
        )->method(
            'getThemeByFullPath'
        )->with(
            $path
        )->will(
            $this->returnValue($theme)
        );
        $collectionFactory->expects($this->once())->method('create')->will($this->returnValue($collectionMock));
        $themeFactory = $this->getMock('\Magento\Theme\Model\ThemeFactory', [], [], '', false);

        $themeProvider = $this->objectManager->getObject(
            'Magento\Theme\Model\Theme\ThemeProvider',
            [
                'collectionFactory' => $collectionFactory,
                'themeFactory' => $themeFactory
            ]
        );

        $this->assertSame($theme, $themeProvider->getThemeByFullPath($path));
    }

    public function testGetById()
    {
        $themeId = 755;
        $collectionFactory = $this->getMock(
            'Magento\Theme\Model\ResourceModel\Theme\CollectionFactory',
            [],
            [],
            '',
            false
        );
        $theme = $this->getMock('Magento\Theme\Model\Theme', ['__sleep', 'load'], [], '', false);
        $theme->expects($this->once())->method('load')->with($themeId)->will($this->returnSelf());
        $theme->expects($this->once())->method('__sleep')->will($this->returnValue([]));

        $themeFactory = $this->getMock('\Magento\Theme\Model\ThemeFactory', ['create'], [], '', false);
        $themeFactory->expects($this->once())->method('create')->will($this->returnValue($theme));

        $cacheMock = $this->getMockBuilder('Magento\Framework\App\CacheInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $cacheMock->expects($this->once())
            ->method('load')
            ->with('theme-by-id-' . $themeId)
            ->willReturn(false);

        $themeProvider = $this->objectManager->getObject(
            'Magento\Theme\Model\Theme\ThemeProvider',
            [
                'collectionFactory' => $collectionFactory,
                'themeFactory' => $themeFactory,
                'cache' => $cacheMock
            ]
        );

        $this->assertSame($theme, $themeProvider->getThemeById($themeId));
    }

    public function testGetThemeCustomizations()
    {
        $collectionFactory = $this->getMockBuilder('Magento\Theme\Model\ResourceModel\Theme\CollectionFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $themeFactory = $this->getMockBuilder('Magento\Theme\Model\ThemeFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $collection = $this->getMockBuilder('Magento\Theme\Model\ResourceModel\Theme\Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('addAreaFilter')
            ->with(Area::AREA_FRONTEND)
            ->willReturnSelf();
        $collection->expects($this->once())
            ->method('addTypeFilter')
            ->with(ThemeInterface::TYPE_VIRTUAL)
            ->willReturnSelf();

        $themeProvider = $this->objectManager->getObject(
            'Magento\Theme\Model\Theme\ThemeProvider',
            [
                'collectionFactory' => $collectionFactory,
                'themeFactory' => $themeFactory
            ]
        );

        $this->assertInstanceOf(get_class($collection), $themeProvider->getThemeCustomizations());
    }
}
