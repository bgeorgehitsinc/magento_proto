<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TestFramework\Annotation;

use Magento\Framework\Component\ComponentRegistrar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Implementation of the @magentoComponentsDir DocBlock annotation
 */
class ComponentRegistrarFixture
{
    /**
     * Annotation name
     */
    const ANNOTATION_NAME = 'magentoComponentsDir';

    /**#@+
     * Properties of components registrar
     */
    const REGISTRAR_CLASS = 'Magento\Framework\Component\ComponentRegistrar';
    const PATHS_FIELD = 'paths';
    /**#@-*/

    /**
     * Fixtures base dir
     *
     * @var string
     */
    private $fixtureBaseDir;

    /**
     * Original values of registered components
     *
     * @var array
     */
    private $origComponents = null;

    /**
     * @var array
     */
    private $fixtureThemes = [];

    /**
     * @var \Magento\Theme\Model\Theme\Registration
     */
    private $registration;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * Constructor
     *
     * @param string $fixtureBaseDir
     */
    public function __construct($fixtureBaseDir)
    {
        $this->fixtureBaseDir = $fixtureBaseDir;
        $this->componentRegistrar = new ComponentRegistrar();
    }

    /**
     * Handler for 'startTest' event
     *
     * @param \PHPUnit_Framework_TestCase $test
     * @return void
     */
    public function startTest(\PHPUnit_Framework_TestCase $test)
    {
        $this->registerComponents($test);
    }

    /**
     * Handler for 'endTest' event
     *
     * @param \PHPUnit_Framework_TestCase $test
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endTest(\PHPUnit_Framework_TestCase $test)
    {
        $this->restoreComponents();
    }

    private function registerComponents(\PHPUnit_Framework_TestCase $test)
    {
        $annotations = $test->getAnnotations();
        $componentAnnotations = [];
        if (isset($annotations['class'][self::ANNOTATION_NAME])) {
            $componentAnnotations = array_merge($componentAnnotations, $annotations['class'][self::ANNOTATION_NAME]);
        }
        if (isset($annotations['method'][self::ANNOTATION_NAME])) {
            $componentAnnotations = array_merge($componentAnnotations, $annotations['method'][self::ANNOTATION_NAME]);
        }
        if (empty($componentAnnotations)) {
            return;
        }
        $componentAnnotations = array_unique($componentAnnotations);
        $reflection = new \ReflectionClass(self::REGISTRAR_CLASS);
        $paths = $reflection->getProperty(self::PATHS_FIELD);
        $paths->setAccessible(true);
        $this->origComponents = $paths->getValue();
        $paths->setAccessible(false);
        foreach ($componentAnnotations as $fixturePath) {
            $fixturesDir = $this->fixtureBaseDir . '/' . $fixturePath;
            if (!file_exists($fixturesDir)) {
                throw new \InvalidArgumentException(
                    self::ANNOTATION_NAME . " fixture '$fixturePath' does not exist"
                );
            }
            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($fixturesDir, \FilesystemIterator::SKIP_DOTS)
                ),
                '/^.+\/registration\.php$/'
            );
            /**
             * @var \SplFileInfo $registrationFile
             */
            foreach ($iterator as $registrationFile) {
                require $registrationFile->getRealPath();
            }
        }
        $currentThemes = array_keys($this->componentRegistrar->getPaths(ComponentRegistrar::THEME));
        $origThemes = array_keys($this->origComponents[ComponentRegistrar::THEME]);
        $this->fixtureThemes = array_diff($currentThemes, $origThemes);
        $this->registerThemes();
    }

    private function restoreComponents()
    {
        if (null !== $this->origComponents) {
            $this->unregisterFixtureThemes();
            $reflection = new \ReflectionClass(self::REGISTRAR_CLASS);
            $paths = $reflection->getProperty(self::PATHS_FIELD);
            $paths->setAccessible(true);
            $paths->setValue($this->origComponents);
            $paths->setAccessible(false);
            $this->origComponents = null;
            $this->fixtureThemes = [];
        }
    }

    /**
     * Initiate themes registration in the collection in case any fixture themes are registered
     *
     * @return void
     */
    private function registerThemes()
    {
        if ($this->fixtureThemes) {
            $this->getThemeRegistration()->register();
        }
    }

    /**
     * Unregister fixture themes
     *
     * @return void
     */
    private function unregisterFixtureThemes()
    {
        foreach ($this->fixtureThemes as $themeName) {
            $theme = $this->registration->getThemeFromDb($themeName);
            $theme->delete();
        }
    }

    /**
     * Get themes registration model
     *
     * @return \Magento\Theme\Model\Theme\Registration
     */
    private function getThemeRegistration()
    {
        if ($this->registration == null) {
            $this->registration = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
                'Magento\Theme\Model\Theme\Registration'
            );
        }
        return $this->registration;
    }
}
