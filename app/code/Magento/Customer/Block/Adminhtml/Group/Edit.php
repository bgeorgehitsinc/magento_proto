<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Block\Adminhtml\Group;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;

/**
 * @deprecated 102.0.0
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupManagementInterface $groupManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        GroupRepositoryInterface $groupRepository,
        GroupManagementInterface $groupManagement,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->groupRepository = $groupRepository;
        $this->groupManagement = $groupManagement;
        parent::__construct($context, $data);
    }

    /**
     * Update Save and Delete buttons. Remove Delete button if group can't be deleted.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_group';
        $this->_blockGroup = 'Magento_Customer';

        $this->buttonList->update('save', 'label', __('Save Customer Group'));
        $this->buttonList->update('delete', 'label', __('Delete Customer Group'));
        $this->buttonList->update(
            'delete',
            'onclick',
            sprintf(
                "deleteConfirm('%s','%s', %s)",
                'Are you sure?',
                $this->getDeleteUrl(),
                json_encode(
                    [
                        'action' => '',
                        'data' => [
                            'form_key' => $this->getFormKey()
                        ]
                    ]
                )
            )
        );

        $groupId = $this->coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        if (!$groupId || $this->groupManagement->isReadonly($groupId)) {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Retrieve the header text, either editing an existing group or creating a new one.
     *
     * @return \Magento\Framework\Phrase
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getHeaderText()
    {
        $groupId = $this->coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        if ($groupId === null) {
            return __('New Customer Group');
        } else {
            $group = $this->groupRepository->getById($groupId);
            return __('Edit Customer Group "%1"', $this->escapeHtml($group->getCode()));
        }
    }

    /**
     * Retrieve CSS classes added to the header.
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-customer-groups';
    }
}
