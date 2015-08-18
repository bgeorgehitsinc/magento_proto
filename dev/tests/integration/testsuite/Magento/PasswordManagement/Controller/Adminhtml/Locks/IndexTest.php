<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PasswordManagement\Controller\Adminhtml\Locks;

class IndexTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * Test index action
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/PasswordManagement/_files/locked_users.php
     */
    public function testIndexAction()
    {
        $this->dispatch('backend/admin/locks/index');

        $body = $this->getResponse()->getBody();
        $this->assertContains('<h1 class="page-title">Locked Users</h1>', $body);
        $this->assertRegExp(
            '/<td data-column\="username"\s*class\="\s*col-name\s*col-username\s*"\s*>\s*adminUser1\s*<\/td>/',
            $body
        );
        $this->assertRegExp(
            '/<td data-column\="username"\s*class\="\s*col-name\s*col-username\s*"\s*>\s*adminUser2\s*<\/td>/',
            $body
        );
    }
}
