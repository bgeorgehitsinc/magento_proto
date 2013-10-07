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
 * @package     Magento_Backend
 * @subpackage  unit_tests
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Backend\Block\Widget\Grid\Column\Renderer;

class CurrencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency
     */
    protected $_blockCurrency;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_appMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_localeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_curLocatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_columnMock;

    /**
     * @var \Magento\Object
     */
    protected $_row;

    protected function setUp()
    {
        $this->_appMock = $this->getMock('Magento\Core\Model\App', array(), array(), '', false);
        $this->_localeMock = $this->getMock('Magento\Core\Model\LocaleInterface');
        $this->_curLocatorMock = $this->getMock(
            'Magento\Directory\Model\Currency\DefaultLocator', array(), array(), '', false
        );
        $this->_columnMock = $this->getMock(
            'Magento\Backend\Block\Widget\Grid\Column', array('getIndex'), array(), '', false
        );
        $this->_columnMock->expects($this->any())->method('getIndex')->will($this->returnValue('columnIndex'));
        $this->_row = new \Magento\Object(array('columnIndex' => '10'));

        $objectManagerHelper = new \Magento\TestFramework\Helper\ObjectManager($this);
        $this->_blockCurrency = $objectManagerHelper->getObject(
            'Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency',
            array(
                'locale' => $this->_localeMock,
                'app' => $this->_appMock,
                'currencyLocator' => $this->_curLocatorMock,
                'urlBuilder' => $this->getMock(
                    'Magento\Backend\Model\Url', array(), array(), '', false
                )
            )
        );
        $this->_blockCurrency->setColumn($this->_columnMock);
    }

    protected function tearDown()
    {
        unset($this->_appMock);
        unset($this->_localeMock);
        unset($this->_curLocatorMock);
        unset($this->_columnMock);
        unset($this->_row);
        unset($this->_blockCurrency);
    }

    public function testRenderWithDefaultCurrency()
    {
        $currencyMock = $this->getMock('Magento\Directory\Model\Currency', array(), array(), '', false);
        $currencyMock->expects($this->once())->method('getRate')->with('defaultCurrency')
            ->will($this->returnValue(1.5));

        $storeMock = $this->getMock('Magento\Core\Model\Store', array(), array(), '', false);
        $storeMock->expects($this->once())->method('getBaseCurrency')->will($this->returnValue($currencyMock));

        $this->_appMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->_curLocatorMock->expects($this->any())->method('getDefaultCurrency')->will($this->returnValue(
            'defaultCurrency'
        ));

        $currLocaleMock = $this->getMock('Zend_Currency', array(), array(), '', false);
        $currLocaleMock->expects($this->once())->method('toCurrency')->with(15.0000)->will($this->returnValue('15USD'));
        $this->_localeMock->expects($this->once())->method('currency')->with('defaultCurrency')
            ->will($this->returnValue($currLocaleMock));

        $this->assertEquals('15USD', $this->_blockCurrency->render($this->_row));
    }
}
