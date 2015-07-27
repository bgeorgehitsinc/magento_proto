<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesRule\Model;

use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use \Magento\SalesRule\Model\Resource\Coupon\Collection;

/**
 * Coupon CRUD class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CouponRepository implements \Magento\SalesRule\Api\CouponRepositoryInterface
{
    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\SalesRule\Api\Data\CouponSearchResultInterfaceFactory
     */
    protected $searchResultFactory;

    /**
     * @var \Magento\SalesRule\Model\Resource\Coupon\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\SalesRule\Model\Spi\CouponResourceInterface
     */
    protected $resourceModel;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @param CouponFactory $couponFactory
     * @param RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Api\Data\CouponSearchResultInterfaceFactory $searchResultFactory
     * @param Resource\Coupon\CollectionFactory $collectionFactory
     * @param Spi\CouponResourceInterface $resourceModel
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Api\Data\CouponSearchResultInterfaceFactory $searchResultFactory,
        \Magento\SalesRule\Model\Resource\Coupon\CollectionFactory $collectionFactory,
        \Magento\SalesRule\Model\Spi\CouponResourceInterface $resourceModel,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->couponFactory = $couponFactory;
        $this->ruleFactory = $ruleFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * Save coupon.
     *
     * @param \Magento\SalesRule\Api\Data\CouponInterface $coupon
     * @return \Magento\SalesRule\Api\Data\CouponInterface
     * @throws \Magento\Framework\Exception\InputException If there is a problem with the input
     * @throws \Magento\Framework\Exception\NoSuchEntityException If a coupon ID is sent but the coupon does not exist
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Magento\SalesRule\Api\Data\CouponInterface $coupon)
    {
        $this->resourceModel->save($coupon);
        return $coupon;
    }

    /**
     * Get coupon by coupon id.
     *
     * @param int $couponId
     * @return \Magento\SalesRule\Api\Data\CouponInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If $couponId is not found
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($couponId)
    {
        $coupon = $this->couponFactory->create()
            ->load($couponId);

        if (!$coupon->getId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }
        return $coupon;
    }

    /**
     * Retrieve coupon.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\SalesRule\Api\Data\CouponSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Magento\SalesRule\Model\Resource\Coupon\Collection $collection */
        $collection = $this->collectionFactory->create();
        $couponInterfaceName = 'Magento\SalesRule\Api\Data\CouponInterface';
        $this->extensionAttributesJoinProcessor->process($collection, $couponInterfaceName);

        //Add filters from root filter group to the collection
        /** @var FilterGroup $group */
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders === null) {
            $sortOrders = [];
        }
        /** @var \Magento\Framework\Api\SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == SearchCriteriaInterface::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->load();

        $coupons = [];
        /** @var \Magento\SalesRule\Model\Coupon $couponModel */
        foreach ($collection as $couponModel) {
            $coupons[] = $couponModel->getData();
        }

        $searchResults = $this->searchResultFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($coupons);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete coupon by coupon id.
     *
     * @param int $couponId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($couponId)
    {
        /** @var \Magento\SalesRule\Model\Coupon $coupon */
        $coupon = $this->couponFactory->create()
            ->load($couponId);

        if (!$coupon->getCouponId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        $this->resourceModel->delete($coupon);
        return true;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param Collection $collection
     * @return void
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        Collection $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $fields[] = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }
        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }
}
