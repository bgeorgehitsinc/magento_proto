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
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Mage_Backend
 * @subpackage  unit_tests
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Test class for Mage_Backend_Block_Widget_Button
 */
class Mage_Backend_Block_Widget_ButtonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $_layoutMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $_helperMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $_factoryMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $_blockMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $_buttonMock;

    protected function setUp()
    {
        $this->_helperMock =
            $this->getMock('Mage_Backend_Helper_Data', array('uniqHash'), array(), '', false, false);

        $this->_layoutMock =
            $this->getMock('Mage_Core_Model_Layout', array(), array(), '', false, false);
        $this->_layoutMock
            ->expects($this->any())
            ->method('helper')
            ->will($this->returnValue($this->_helperMock));

        $arguments = array(
            'urlBuilder' =>
                $this->getMock('Mage_Backend_Model_Url', array(), array(), '', false, false),
            'layout' => $this->_layoutMock
        );

        $objectManagerHelper = new Magento_Test_Helper_ObjectManager($this);
        $this->_blockMock =
            $objectManagerHelper->getBlock('Mage_Backend_Block_Widget_Button', $arguments);
    }

    public function tearDown()
    {
        unset($this->_layoutMock);
        unset($this->_helperMock);
        unset($this->_buttonMock);
    }

    /**
     * @covers Mage_Backend_Block_Widget_Button::getAttributesHtml
     * @dataProvider getAttributesHtmlDataProvider
     */
    public function testGetAttributesHtml($data, $expect)
    {
        $this->_blockMock->setData($data);
        $attributes = $this->_blockMock->getAttributesHtml();
        $this->assertRegExp($expect, $attributes);
    }

    public function getAttributesHtmlDataProvider()
    {
        return array(
            array(
                array(
                    'data_attribute' => array(
                        'validation' => array(
                            'required' => true
                        ),
                    ),
                ),
                '/data-validation="[^"]*" /'
            ),
            array(
                array(
                    'data_attribute' => array(
                        'mage-init' => array(
                            'button' => array('someKey' => 'someValue')
                        ),
                    ),
                ),
                '/data-mage-init="[^"]*" /'
            ),
            array(
                array(
                    'data_attribute' => array(
                        'mage-init' => array(
                            'button' => array('someKey' => 'someValue')
                        ),
                        'validation' => array('required' => true),
                    ),
                ),
                '/data-mage-init="[^"]*" data-validation="[^"]*" /'
            ),
        );
    }
}
