<?php
/**
 * Customer Service Address Interface
 *
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
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Customer\Service\V1\Dto;

use Magento\Customer\Service\V1\Dto\Filter;

class SearchCriteriaBuilder extends \Magento\Service\Entity\AbstractDtoBuilder
{
    /**
     * @param Filter $filter
     *
     * @return SearchCriteriaBuilder
     */
    public function addFilter($filter)
    {
        if (!isset($this->_data['filters'])) {
            $this->_data['filters'] = array();
        }

        $this->_data['filters'][] = $filter;
        return $this;
    }

    /**
     * @param string $field
     * @param int $direction
     *
     * @return SearchCriteriaBuilder
     */
    public function addSortOrder($field, $direction)
    {
        if (!isset($this->_data['sort_orders'])) {
            $this->_data['sort_orders'] = array();
        }

        $this->_data['sort_orders'][$field] = $direction;
        return $this;
    }

    /**
     * @param int $pageSize
     *
     * @return SearchCriteriaBuilder
     */
    public function setPageSize($pageSize)
    {
        return $this->_set('page_size', $pageSize);
    }

    /**
     * @param int $currentPage
     *
     * @return SearchCriteriaBuilder
     */
    public function setCurrentPage($currentPage)
    {
        return $this->_set('current_page', $currentPage);
    }
}
