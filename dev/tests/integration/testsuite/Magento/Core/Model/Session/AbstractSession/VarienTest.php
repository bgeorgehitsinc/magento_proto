<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an e-mail
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Core
 * @subpackage  integration_tests
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Test class for \Magento\Core\Model\Session\AbstractSession\Varien
 *
 */
namespace Magento\Core\Model\Session\AbstractSession;

class VarienTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $saveMethod
     * @param string $iniValue
     * @dataProvider sessionSaveMethodDataProvider
     */
    public function testSessionSaveMethod($saveMethod, $iniValue)
    {
        $this->markTestIncomplete('Bug MAGE-5487');

        // depending on configuration some values cannot be set as default save session handlers.
        // in such cases warnings will be generated by php and test will fail
        $origErrorRep = error_reporting(E_ALL ^ E_WARNING);
        $origSessionHandler = ini_set('session.save_handler', $iniValue);

        if ($iniValue && (ini_get('session.save_handler') != $iniValue)) {
            ini_set('session.save_handler', $origSessionHandler);
            error_reporting($origErrorRep);
            $this->markTestSkipped("Can't  set '$iniValue' as session save handler");
        }

        ini_set('session.save_handler', $origSessionHandler);

        /** @var $configModel \Magento\Core\Model\Config */
        $configModel = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get('Magento\Core\Model\Config');
        $configModel->setNode(\Magento\Core\Model\Session\AbstractSession::XML_NODE_SESSION_SAVE, $saveMethod);
        /**
         * @var \Magento\Core\Model\Session\AbstractSession\Varien
         */
        $model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create('Magento\Core\Model\Session\AbstractSession');
        //There is no any possibility to determine whether session already started or not in php before 5.4
        $model->setSkipEmptySessionCheck(true);
        $model->start();
        if ($iniValue) {
            $this->assertEquals(ini_get('session.save_handler'), $iniValue);
        }
        ini_set('session.save_handler', $origSessionHandler);
        error_reporting($origErrorRep);
    }

    /**
     * @return array
     */
    public function sessionSaveMethodDataProvider()
    {
        return array(
            array('db', 'user'),
            array('memcache', 'memcache'),
            array('memcached', 'memcached'),
            array('eaccelerator', 'eaccelerator'),
            array('', ''),
            array('dummy', ''),
        );
    }
}
