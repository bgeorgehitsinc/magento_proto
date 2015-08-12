<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Setup\Test\Unit\Model\Cron;

use Magento\Framework\ObjectManagerInterface;
use Magento\Setup\Model\Cron\ComponentUninstallerFactory;
use Magento\Setup\Model\Cron\JobComponentUninstall;

class JobComponentUninstallTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobComponentUninstall
     */
    private $job;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Setup\Model\Cron\Status
     */
    private $status;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Setup\Model\Updater
     */
    private $updater;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Setup\Model\ObjectManagerProvider
     */
    private $objectManagerProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Setup\Model\ModuleUninstaller
     */
    private $moduleUninstaller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Setup\Model\ModuleRegistryUninstaller
     */
    private $moduleRegistryUninstaller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Theme\Model\Theme\ThemeUninstaller
     */
    private $themeUninstaller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Theme\Model\Theme\ThemePackageInfo
     */
    private $themePackageInfo;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Composer\ComposerInformation
     */
    private $composerInformation;

    public function setUp()
    {
        $this->output = $this->getMockForAbstractClass(
            'Symfony\Component\Console\Output\OutputInterface',
            [],
            '',
            false
        );
        $this->status = $this->getMock('Magento\Setup\Model\Cron\Status', [], [], '', false);
        $this->moduleUninstaller = $this->getMock('Magento\Setup\Model\ModuleUninstaller', [], [], '', false);
        $this->moduleRegistryUninstaller = $this->getMock(
            'Magento\Setup\Model\ModuleRegistryUninstaller',
            [],
            [],
            '',
            false
        );
        $this->themeUninstaller = $this->getMock('Magento\Theme\Model\Theme\ThemeUninstaller', [], [], '', false);
        $this->themePackageInfo = $this->getMock('Magento\Theme\Model\Theme\ThemePackageInfo', [], [], '', false);
        $this->composerInformation = $this->getMock(
            'Magento\Framework\Composer\ComposerInformation',
            [],
            [],
            '',
            false
        );
        $this->objectManagerProvider = $this->getMock('Magento\Setup\Model\ObjectManagerProvider', [], [], '', false);
        $this->objectManager = $this->getMockForAbstractClass(
            'Magento\Framework\ObjectManagerInterface',
            [],
            '',
            false
        );

        $packageInfoFactory = $this->getMock('Magento\Framework\Module\PackageInfoFactory', [], [], '', false);
        $packageInfo = $this->getMock('Magento\Framework\Module\PackageInfo', [], [], '', false);
        $packageInfoFactory->expects($this->any())->method('create')->willReturn($packageInfo);
        $cache = $this->getMock('Magento\Framework\App\Cache', [], [], '', false);
        $cleanupFiles = $this->getMock('Magento\Framework\App\State\CleanupFiles', [], [], '', false);
        $cache->expects($this->any())->method('clean');
        $cleanupFiles->expects($this->any())->method('clearCodeGeneratedClasses');
        $cleanupFiles->expects($this->any())->method('clearMaterializedViewFiles');
        $this->objectManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['Magento\Framework\App\Cache', $cache],
                        ['Magento\Framework\App\State\CleanupFiles', $cleanupFiles],
                        ['Magento\Framework\Module\PackageInfoFactory', $packageInfoFactory],
                    ]
                )
            );
        $this->objectManagerProvider->expects($this->any())->method('get')->willReturn($this->objectManager);
        $this->updater = $this->getMock('Magento\Setup\Model\Updater', [], [], '', false);
    }

    private function setUpUpdater()
    {
        $this->updater->expects($this->any())->method('createUpdaterTask')->willReturn('');
    }

    public function testExecuteModule()
    {
        $this->setUpUpdater();

        $this->moduleUninstaller->expects($this->once())->method('uninstallData');
        $this->moduleRegistryUninstaller->expects($this->once())->method('removeModulesFromDb');
        $this->moduleRegistryUninstaller->expects($this->once())->method('removeModulesFromDeploymentConfig');

        $this->job = new JobComponentUninstall(
            $this->composerInformation,
            $this->moduleUninstaller,
            $this->moduleRegistryUninstaller,
            $this->themeUninstaller,
            $this->themePackageInfo,
            $this->objectManagerProvider,
            $this->output,
            $this->status,
            $this->updater,
            'setup:component:uninstall',
            [
                'components' => [
                    [
                        JobComponentUninstall::COMPONENT_NAME => 'vendor/module-package',
                    ]
                ],
                'dataOption' => true
            ]
        );

        $this->composerInformation->expects($this->once())
            ->method('getInstalledMagentoPackages')
            ->willReturn(['vendor/module-package' => ['type' => JobComponentUninstall::COMPONENT_MODULE]]);
        $this->job->execute();
    }

    public function testExecuteLanguage()
    {
        $this->setUpUpdater();
        $this->composerInformation->expects($this->once())
            ->method('getInstalledMagentoPackages')
            ->willReturn(['vendor/language-a' => ['type' => JobComponentUninstall::COMPONENT_LANGUAGE]]);

        $this->job = new JobComponentUninstall(
            $this->composerInformation,
            $this->moduleUninstaller,
            $this->moduleRegistryUninstaller,
            $this->themeUninstaller,
            $this->themePackageInfo,
            $this->objectManagerProvider,
            $this->output,
            $this->status,
            $this->updater,
            'setup:component:uninstall',
            [
                'components' => [
                    [
                        JobComponentUninstall::COMPONENT_NAME => 'vendor/language-a',
                    ]
                ]
            ]
        );
        $this->job->execute();
    }

    public function testExecuteTheme()
    {
        $this->setUpUpdater();
        $this->composerInformation->expects($this->once())
            ->method('getInstalledMagentoPackages')
            ->willReturn(['vendor/theme-a' => ['type' => JobComponentUninstall::COMPONENT_THEME]]);
        $this->themeUninstaller->expects($this->once())->method('uninstallRegistry');

        $this->job = new JobComponentUninstall(
            $this->composerInformation,
            $this->moduleUninstaller,
            $this->moduleRegistryUninstaller,
            $this->themeUninstaller,
            $this->themePackageInfo,
            $this->objectManagerProvider,
            $this->output,
            $this->status,
            $this->updater,
            'setup:component:uninstall',
            [
                'components' => [
                    [
                        JobComponentUninstall::COMPONENT_NAME => 'vendor/theme-a',
                    ]
                ]
            ]
        );
        $this->job->execute();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown component type
     */
    public function testExecuteUnknownType()
    {
        $this->setUpUpdater();
        $this->composerInformation->expects($this->once())
            ->method('getInstalledMagentoPackages')
            ->willReturn(['vendor/unknown-a' => ['type' => 'unknown']]);

        $this->job = new JobComponentUninstall(
            $this->composerInformation,
            $this->moduleUninstaller,
            $this->moduleRegistryUninstaller,
            $this->themeUninstaller,
            $this->themePackageInfo,
            $this->objectManagerProvider,
            $this->output,
            $this->status,
            $this->updater,
            'setup:component:uninstall',
            [
                'components' => [
                    [
                        JobComponentUninstall::COMPONENT_NAME => 'vendor/unknown-a',
                    ]
                ]
            ]
        );
        $this->job->execute();
    }

    /**
     * @param array $params
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Job parameter format is incorrect
     * @dataProvider executeWrongFormatDataProvider
     */
    public function testExecuteWrongFormat(array $params)
    {
        $this->job = new JobComponentUninstall(
            $this->composerInformation,
            $this->moduleUninstaller,
            $this->moduleRegistryUninstaller,
            $this->themeUninstaller,
            $this->themePackageInfo,
            $this->objectManagerProvider,
            $this->output,
            $this->status,
            $this->updater,
            'setup:component:uninstall',
            $params
        );
        $this->job->execute();
    }

    public function executeWrongFormatDataProvider()
    {
        return [
            'empty' => [[]],
            'no name' => [['components' => [['key' => 'value']]]],
            'components not array' => [['components' => '']],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage error
     */
    public function testExecuteUpdateFails()
    {
        $this->updater->expects($this->once())->method('createUpdaterTask')->willReturn('error');
        $this->composerInformation->expects($this->once())
            ->method('getInstalledMagentoPackages')
            ->willReturn(['vendor/language-a' => ['type' => JobComponentUninstall::COMPONENT_LANGUAGE]]);

        $this->job = new JobComponentUninstall(
            $this->composerInformation,
            $this->moduleUninstaller,
            $this->moduleRegistryUninstaller,
            $this->themeUninstaller,
            $this->themePackageInfo,
            $this->objectManagerProvider,
            $this->output,
            $this->status,
            $this->updater,
            'setup:component:uninstall',
            [
                'components' => [
                    [
                        JobComponentUninstall::COMPONENT_NAME => 'vendor/language-a',
                    ]
                ]
            ]
        );
        $this->job->execute();
    }
}
