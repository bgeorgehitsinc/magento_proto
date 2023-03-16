<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesSequence\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context as DatabaseContext;
use Magento\SalesSequence\Model\Profile as ModelProfile;
use Magento\SalesSequence\Model\ProfileFactory;

/**
 * Class Profile represents profile data for sequence as prefix, suffix, start value etc.
 *
 * @api
 * @since 100.0.2
 */
class Profile extends AbstractDb
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'sales_sequence_profile';

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_sequence_profile', 'profile_id');
    }

    /**
     * @param DatabaseContext $context
     * @param ProfileFactory $profileFactory
     * @param string $connectionName
     */
    public function __construct(
        DatabaseContext $context,
        protected readonly ProfileFactory $profileFactory,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Load active profile
     *
     * @param int $metadataId
     * @return ModelProfile
     * @throws LocalizedException
     */
    public function loadActiveProfile($metadataId)
    {
        $profile = $this->profileFactory->create();
        $connection = $this->getConnection();
        $bind = ['meta_id' => $metadataId];
        $select = $connection->select()
            ->from($this->getMainTable(), ['profile_id'])
            ->where('meta_id = :meta_id')
            ->where('is_active = 1');

        $profileId = $connection->fetchOne($select, $bind);

        if ($profileId) {
            $this->load($profile, $profileId);
        }
        return $profile;
    }
}
