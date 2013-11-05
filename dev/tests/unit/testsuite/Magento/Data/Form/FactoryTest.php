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
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Data\Form;

/**
 * Tests for \Magento\Data\Form\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_objectManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_sessionMock;

    protected function setUp()
    {
        $this->_objectManagerMock = $this->getMock('Magento\ObjectManager\ObjectManager', array(), array(), '', false);
        $this->_sessionMock = $this->getMock('Magento\Core\Model\Session', array(), array(), '', false);
    }

    /**
     * @expectedException \Magento\Exception
     * @expectedExceptionMessage WrongClass doesn't extends \Magento\Data\Form
     */
    public function testWrongTypeException()
    {
        $className = 'WrongClass';

        $formMock = $this->getMock($className, array(), array(), '', false);
        $this->_objectManagerMock->expects($this->once())->method('create')->will($this->returnValue($formMock));

        $formFactory = new Factory($this->_objectManagerMock, $this->_sessionMock, $className);
        $formFactory->create();
    }

    public function testCreate()
    {
        $className = 'Magento\Data\Form';
        $formMock = $this->getMock($className, array(), array(), '', false);
        $this->_objectManagerMock->expects($this->once())
            ->method('create')
            ->with($className)
            ->will($this->returnValue($formMock));
        $formMock->expects($this->once())
            ->method('setSession')
            ->with($this->_sessionMock);

        $formFactory = new Factory($this->_objectManagerMock, $this->_sessionMock, $className);
        $this->assertSame($formMock, $formFactory->create());
    }
}
