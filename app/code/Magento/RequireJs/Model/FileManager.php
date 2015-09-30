<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\RequireJs\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\RequireJs\Config;

/**
 * A service for handling RequireJS files in the application
 */
class FileManager
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;

    /**
     * @param Config $config
     * @param \Magento\Framework\Filesystem $appFilesystem
     * @param AppState $appState
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     */
    public function __construct(
        Config $config,
        \Magento\Framework\Filesystem $appFilesystem,
        AppState $appState,
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->config = $config;
        $this->filesystem = $appFilesystem;
        $this->appState = $appState;
        $this->assetRepo = $assetRepo;
    }

    /**
     * Create a view asset representing the aggregated configuration file
     *
     * @return \Magento\Framework\View\Asset\File
     */
    public function createRequireJsConfigAsset()
    {
        $relPath = $this->config->getConfigFileRelativePath();
        $this->ensureSourceFile($relPath);
        return $this->assetRepo->createArbitrary($relPath, '');
    }

    /**
     * Create '.min' files resolver asset
     *
     * @return \Magento\Framework\View\Asset\File
     */
    public function createMinResolverAsset()
    {
        $relPath = $this->config->getMinResolverRelativePath();
        $this->ensureMinResolverFile($relPath);
        return $this->assetRepo->createArbitrary($relPath, '');
    }

    /**
     * Create a view asset representing the aggregated configuration file
     *
     * @return \Magento\Framework\View\Asset\File
     */
    public function createRequireJsMixinsAsset()
    {
        return $this->assetRepo->createArbitrary($this->config->getMixinsFileRelativePath(), '');
    }

    /**
     * Create a view asset representing the aggregated configuration file
     *
     * @return \Magento\Framework\View\Asset\File
     */
    public function createRequireJsAsset()
    {
        return $this->assetRepo->createArbitrary($this->config->getRequireJsFileRelativePath(), '');
    }

    /**
     * Make sure the aggregated configuration is materialized
     *
     * By default write the file if it doesn't exist, but in developer mode always do it.
     *
     * @param string $relPath
     * @return void
     */
    private function ensureSourceFile($relPath)
    {
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        if ($this->appState->getMode() == AppState::MODE_DEVELOPER || !$dir->isExist($relPath)) {
            $dir->writeFile($relPath, $this->config->getConfig());
        }
    }

    /**
     * Make sure the '.min' assets resolver is materialized
     *
     * @param string $relPath
     * @return void
     */
    private function ensureMinResolverFile($relPath)
    {
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        if ($this->appState->getMode() == AppState::MODE_DEVELOPER || !$dir->isExist($relPath)) {
            $dir->writeFile($relPath, $this->config->getMinResolverCode());
        }
    }

    /**
     * Create a view asset representing the static js functionality
     *
     * @return \Magento\Framework\View\Asset\File
     */
    public function createStaticJsAsset()
    {
        if ($this->appState->getMode() != AppState::MODE_PRODUCTION) {
            return false;
        }
        $libDir = $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW);
        $relPath = $libDir->getRelativePath(Config::STATIC_FILE_NAME);
        /** @var $context \Magento\Framework\View\Asset\File\FallbackContext */
        $context = $this->assetRepo->getStaticViewFileContext();

        return $this->assetRepo->createArbitrary($relPath, $context->getPath());
    }

    /**
     * Create a view assets representing the bundle js functionality
     *
     * @return \Magento\Framework\View\Asset\File[]
     */
    public function createBundleJsPool()
    {
        $bundles = [];
        if ($this->appState->getMode() == AppState::MODE_PRODUCTION) {
            $libDir = $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW);
            /** @var $context \Magento\Framework\View\Asset\File\FallbackContext */
            $context = $this->assetRepo->getStaticViewFileContext();

            $bundleDir = $context->getPath() . '/' . Config::BUNDLE_JS_DIR;

            if (!$libDir->isExist($bundleDir)) {
                return [];
            }

            foreach ($libDir->read($bundleDir) as $bundleFile) {
                $relPath = $libDir->getRelativePath($bundleFile);
                $bundles[] = $this->assetRepo->createArbitrary($relPath, '');
            }
        }

        return $bundles;
    }

    /**
     * Remove all bundles from pool
     *
     * @return bool
     */
    public function clearBundleJsPool()
    {
        $dirWrite = $this->filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        /** @var $context \Magento\Framework\View\Asset\File\FallbackContext */
        $context = $this->assetRepo->getStaticViewFileContext();
        $bundleDir = $context->getPath() . '/' . Config::BUNDLE_JS_DIR;
        return $dirWrite->delete($bundleDir);
    }
}
