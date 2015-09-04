<?php
/**
 * Encapsulates directories structure of a Magento module
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Module;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Stdlib\StringUtils as StringHelper;
use Magento\Framework\Component\ComponentRegistrarInterface;

class Dir
{
    /**#@+
     * Directories within modules
     */
    const MODULE_ETC_DIR = 'etc';
    const MODULE_I18N_DIR = 'i18n';
    const MODULE_VIEW_DIR = 'view';
    const MODULE_CONTROLLER_DIR = 'Controller';
    /**#@-*/

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $_string;

    /**
     * Module registry
     *
     * @var ComponentRegistrarInterface
     */
    private $moduleRegistry;

    /**
     * @param StringHelper $string
     * @param ComponentRegistrarInterface $moduleRegistry
     */
    public function __construct(
        StringHelper $string,
        ComponentRegistrarInterface $moduleRegistry
    ) {
        $this->_string = $string;
        $this->moduleRegistry = $moduleRegistry;
    }

    /**
     * Retrieve full path to a directory of certain type within a module
     *
     * @param string $moduleName Fully-qualified module name
     * @param string $type Type of module's directory to retrieve
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getDir($moduleName, $type = '')
    {
        $path = $this->moduleRegistry->getPath(ComponentRegistrar::MODULE, $moduleName);

        if ($type) {
            if (!in_array($type, [
                self::MODULE_ETC_DIR,
                self::MODULE_I18N_DIR,
                self::MODULE_VIEW_DIR,
                self::MODULE_CONTROLLER_DIR
            ])) {
                throw new \InvalidArgumentException("Directory type '{$type}' is not recognized.");
            }
            $path .= '/' . $type;
        }

        return $path;
    }
}
