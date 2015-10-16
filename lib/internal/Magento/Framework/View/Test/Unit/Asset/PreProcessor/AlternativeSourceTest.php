<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Test\Unit\Asset\PreProcessor;

use Magento\Framework\Filesystem;
use Magento\Framework\View\Asset\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Asset\LocalInterface;
use Magento\Framework\View\Asset\PreProcessor\Chain;
use Magento\Framework\View\Asset\File\FallbackContext;
use Magento\Framework\View\Asset\LockerProcessInterface;
use Magento\Framework\View\Asset\ContentProcessorInterface;
use Magento\Framework\View\Asset\PreProcessor\AlternativeSource;
use Magento\Framework\View\Asset\PreProcessor\Helper\SortInterface;
use Magento\Framework\View\Asset\PreProcessor\AlternativeSource\AssetBuilder;

/**
 * Class AlternativeSourceTest
 *
 * @see \Magento\Framework\View\Asset\PreProcessor\AlternativeSource
 */
class AlternativeSourceTest extends \PHPUnit_Framework_TestCase
{
    const AREA = 'test-area';

    const THEME = 'test-theme';

    const LOCALE = 'test-locale';

    const FILE_PATH = 'test-file';

    const MODULE = 'test-module';

    const NEW_CONTENT = 'test-new-content';

    /**
     * @var SortInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sorterMock;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManagerMock;

    /**
     * @var LockerProcessInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lockerProcessMock;

    /**
     * @var AssetBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $assetBuilderMock;

    /**
     * @var ContentProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $alternativeMock;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->sorterMock = $this->getMockBuilder('Magento\Framework\View\Asset\PreProcessor\Helper\SortInterface')
            ->getMockForAbstractClass();
        $this->objectManagerMock = $this->getMockBuilder('Magento\Framework\ObjectManagerInterface')
            ->getMockForAbstractClass();
        $this->lockerProcessMock = $this->getMockBuilder('Magento\Framework\View\Asset\LockerProcessInterface')
            ->getMockForAbstractClass();
        $this->assetBuilderMock = $this->getMockBuilder(
            'Magento\Framework\View\Asset\PreProcessor\AlternativeSource\AssetBuilder'
        )->disableOriginalConstructor()
            ->getMock();
        $this->alternativeMock = $this->getMockBuilder('Magento\Framework\View\Asset\ContentProcessorInterface')
            ->getMockForAbstractClass();
    }

    /**
     * Run test for process method (exception)
     *
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage "stdClass" has to implement the ContentProcessorInterface.
     */
    public function testProcessException()
    {
        $alternatives = [
            'processor' => [
                AlternativeSource::PROCESSOR_CLASS => 'stdClass'
            ]
        ];

        $this->lockerProcessMock->expects(self::once())
            ->method('lockProcess')
            ->with(self::isType('string'));
        $this->lockerProcessMock->expects(self::once())
            ->method('unlockProcess');

        $this->sorterMock->expects(self::once())
            ->method('sort')
            ->with($alternatives)
            ->willReturn($alternatives);

        $this->assetBuilderMock->expects(self::once())
            ->method('setArea')
            ->with(self::AREA)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setTheme')
            ->with(self::THEME)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setLocale')
            ->with(self::LOCALE)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setModule')
            ->with(self::MODULE)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setPath')
            ->with(self::FILE_PATH)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('build')
            ->willReturn($this->getAssetNew());

        $this->objectManagerMock->expects(self::once())
            ->method('get')
            ->with('stdClass')
            ->willReturn(new \stdClass());

        $alternativeSource = new AlternativeSource(
            $this->objectManagerMock,
            $this->lockerProcessMock,
            $this->sorterMock,
            $this->assetBuilderMock,
            'lock',
            $alternatives
        );

        $alternativeSource->process($this->getChainMockExpects('', 0));
    }

    /**
     * Run test for process method
     */
    public function testProcess()
    {
        $alternatives = [
            'processor' => [
                AlternativeSource::PROCESSOR_CLASS => 'Magento\Framework\View\Asset\ContentProcessorInterface'
            ]
        ];

        $this->lockerProcessMock->expects(self::once())
            ->method('lockProcess')
            ->with(self::isType('string'));
        $this->lockerProcessMock->expects(self::once())
            ->method('unlockProcess');

        $this->sorterMock->expects(self::once())
            ->method('sort')
            ->with($alternatives)
            ->willReturn($alternatives);

        $assetMock = $this->getAssetNew();

        $this->assetBuilderMock->expects(self::once())
            ->method('setArea')
            ->with(self::AREA)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setTheme')
            ->with(self::THEME)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setLocale')
            ->with(self::LOCALE)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setModule')
            ->with(self::MODULE)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('setPath')
            ->with(self::FILE_PATH)
            ->willReturnSelf();
        $this->assetBuilderMock->expects(self::once())
            ->method('build')
            ->willReturn($assetMock);

        $this->objectManagerMock->expects(self::once())
            ->method('get')
            ->with('Magento\Framework\View\Asset\ContentProcessorInterface')
            ->willReturn($this->getProcessorMock($assetMock));

        $alternativeSource = new AlternativeSource(
            $this->objectManagerMock,
            $this->lockerProcessMock,
            $this->sorterMock,
            $this->assetBuilderMock,
            'lock',
            $alternatives
        );

        $alternativeSource->process($this->getChainMockExpects());
    }

    /**
     * Run test for process method (content not empty)
     */
    public function testProcessContentNotEmpty()
    {
        $chainMock = $this->getChainMock();
        $assetMock = $this->getAssetMock();

        $chainMock->expects(self::once())
            ->method('getContent')
            ->willReturn('test-content');

        $chainMock->expects(self::once())
            ->method('getAsset')
            ->willReturn($assetMock);

        $this->lockerProcessMock->expects(self::never())
            ->method('lockProcess');
        $this->lockerProcessMock->expects(self::never())
            ->method('unlockProcess');

        $alternativeSource = new AlternativeSource(
            $this->objectManagerMock,
            $this->lockerProcessMock,
            $this->sorterMock,
            $this->assetBuilderMock,
            'lock',
            []
        );

        $alternativeSource->process($chainMock);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $asset
     * @return ContentProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProcessorMock($asset)
    {
        $processorMock = $this->getMockBuilder('Magento\Framework\View\Asset\ContentProcessorInterface')
            ->getMockForAbstractClass();

        $processorMock->expects(self::once())
            ->method('processContent')
            ->with($asset)
            ->willReturn(self::NEW_CONTENT);

        return $processorMock;
    }

    /**
     * @return Chain|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getChainMock()
    {
        $chainMock = $this->getMockBuilder('Magento\Framework\View\Asset\PreProcessor\Chain')
            ->disableOriginalConstructor()
            ->getMock();

        return $chainMock;
    }

    /**
     * @param string $content
     * @param int $contentExactly
     * @return Chain|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getChainMockExpects($content = '', $contentExactly = 1)
    {
        $chainMock = $this->getChainMock();

        $chainMock->expects(self::once())
            ->method('getContent')
            ->willReturn($content);
        $chainMock->expects(self::exactly(3))
            ->method('getAsset')
            ->willReturn($this->getAssetMockExpects());
        $chainMock->expects(self::exactly($contentExactly))
            ->method('setContent')
            ->willReturn(self::NEW_CONTENT);

        return $chainMock;
    }

    /**
     * @return File|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getAssetNew()
    {
        $assetMock = $this->getMockBuilder('Magento\Framework\View\Asset\File')
            ->disableOriginalConstructor()
            ->getMock();

        return $assetMock;
    }

    /**
     * @return LocalInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getAssetMock()
    {
        $assetMock = $this->getMockBuilder('Magento\Framework\View\Asset\LocalInterface')
            ->disableOriginalConstructor()
            ->getMock();

        return $assetMock;
    }

    /**
     * @return LocalInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getAssetMockExpects()
    {
        $assetMock = $this->getAssetMock();

        $assetMock->expects(self::once())
            ->method('getContext')
            ->willReturn($this->getContextMock());
        $assetMock->expects(self::once())
            ->method('getFilePath')
            ->willReturn(self::FILE_PATH);
        $assetMock->expects(self::once())
            ->method('getModule')
            ->willReturn(self::MODULE);

        return $assetMock;
    }

    /**
     * @return FallbackContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getContextMock()
    {
        $contextMock = $this->getMockBuilder('Magento\Framework\View\Asset\File\FallbackContext')
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects(self::once())
            ->method('getAreaCode')
            ->willReturn(self::AREA);
        $contextMock->expects(self::once())
            ->method('getThemePath')
            ->willReturn(self::THEME);
        $contextMock->expects(self::once())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        return $contextMock;
    }
}
