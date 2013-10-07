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
 * @package     Magento_GoogleShopping
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Content Config model
 *
 * @category   Magento
 * @package    Magento_GoogleShopping
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\GoogleShopping\Model;

class Config extends \Magento\Object
{
    /**
     * Config values cache
     *
     * @var array
     */
    protected $_config = array();

    /**
     * @var \Magento\Core\Helper\Data|null
     */
    protected $_coreData = null;

    /**
     * Core store config
     *
     * @var \Magento\Core\Model\Store\Config
     */
    protected $_coreStoreConfig;

    /**
     * Store manager
     *
     * @var \Magento\Core\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Core\Helper\Data $coreData
     *
     * @param \Magento\Core\Model\Store\Config $coreStoreConfig
     * @param \Magento\Core\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Core\Helper\Data $coreData,
        \Magento\Core\Model\Store\Config $coreStoreConfig,
        \Magento\Core\Model\StoreManagerInterface $storeManager,
        array $data = array()
    ) {
        $this->_coreStoreConfig = $coreStoreConfig;
        $this->_coreData = $coreData;
        $this->_storeManager = $storeManager;
        parent::__construct($data);
    }

    /**
     *  Return config var
     *
     *  @param    string $key Var path key
     *  @param    int $storeId Store View Id
     *  @return   mixed
     */
    public function getConfigData($key, $storeId = null)
    {
        if (!isset($this->_config[$key][$storeId])) {
            $value = $this->_coreStoreConfig->getConfig('google/googleshopping/' . $key, $storeId);
            $this->_config[$key][$storeId] = $value;
        }
        return $this->_config[$key][$storeId];
    }

    /**
     * Google Account ID
     *
     * @param int $storeId
     * @return string
     */
    public function getAccountId($storeId = null)
    {
        return $this->getConfigData('account_id', $storeId);
    }

    /**
     * Google Account login
     *
     * @param int $storeId
     * @return string
     */
    public function getAccountLogin($storeId = null)
    {
        return $this->getConfigData('login', $storeId);
    }

    /**
     * Google Account password
     *
     * @param int $storeId
     * @return string
     */
    public function getAccountPassword($storeId = null)
    {
        return $this->_coreData->decrypt($this->getConfigData('password', $storeId));
    }

    /**
     * Google Account type
     *
     * @param int $storeId
     * @return string
     */
    public function getAccountType($storeId = null)
    {
        return $this->getConfigData('account_type', $storeId);
    }

    /**
     * Google Account target country info
     *
     * @param int $storeId
     * @return array
     */
    public function getTargetCountryInfo($storeId = null)
    {
        return $this->getCountryInfo($this->getTargetCountry($storeId), null, $storeId);
    }

    /**
     * Google Account target country
     *
     * @param int $storeId
     * @return string Two-letters country ISO code
     */
    public function getTargetCountry($storeId = null)
    {
        return $this->getConfigData('target_country', $storeId);
    }

    /**
     * Google Account target currency (for target country)
     *
     * @param int $storeId
     * @return string Three-letters currency ISO code
     */
    public function getTargetCurrency($storeId = null)
    {
        $country = $this->getTargetCountry($storeId);
        return $this->getCountryInfo($country, 'currency');
    }

    /**
     * Google Content destinations info
     *
     * @param int $storeId
     * @return array
     */
    public function getDestinationsInfo($storeId = null)
    {
        $destinations = $this->getConfigData('destinations', $storeId);
        $destinationsInfo = array();
        foreach ($destinations as $key => $name) {
            $destinationsInfo[$name] = $this->getConfigData($key, $storeId);
        }

        return $destinationsInfo;
    }

    /**
     * Check whether System Base currency equals Google Content target currency or not
     *
     * @param int $storeId
     * @return boolean
     */
    public function isValidDefaultCurrencyCode($storeId = null)
    {
        return $this->_storeManager->getStore($storeId)->getDefaultCurrencyCode() == $this->getTargetCurrency($storeId);
    }

    /**
     * Google Content supported countries
     *
     * @param int $storeId
     * @return array
     */
    public function getAllowedCountries($storeId = null)
    {
        return $this->getConfigData('allowed_countries', $storeId);
    }

    /**
     * Country info such as name, locale, language etc.
     *
     * @param string $iso two-letters country ISO code
     * @param string $field If specified, return value for field
     * @param int $storeId
     * @return array|string
     */
    public function getCountryInfo($iso, $field = null, $storeId = null)
    {
        $countries = $this->getAllowedCountries($storeId);
        $country = isset($countries[$iso]) ? $countries[$iso] : null;
        $data = isset($country[$field]) ? $country[$field] : null;
        return is_null($field) ? $country : $data;
    }

    /**
     * Returns attributes by ISO country code (grouped by destination)
     *
     * @param string $isoCountryCode
     * @return array
     */
    public function getAttributesByCountry($isoCountryCode)
    {
        $attributesTree = $this->getAttributes();
        foreach ($this->getAttributes() as $destination => $attributes) {
            foreach ($attributes as $attribute => $params) {
                if (!empty($params['country']) && !in_array($isoCountryCode, explode(',', $params['country']))) {
                    unset($attributesTree[$destination][$attribute]);
                }
            }
        }

        return $attributesTree;
    }

    /**
     * Returns all attributes (grouped by destination)
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->getConfigData('attributes');
    }

    /**
     * Get flat array with attribute groups
     * where: key - attribute name, value - group name
     *
     * @return array
     */
    public function getAttributeGroupsFlat()
    {
        $groups = $this->getConfigData('attribute_groups');
        $groupFlat = array();
        foreach ($groups as $group => $subAttributes) {
            foreach ($subAttributes as $subAttribute => $value) {
                $groupFlat[$subAttribute] = $group;
            }
        }
        return $groupFlat;
    }

    /**
     * Get array of base attribute names
     *
     * @return array
     */
    public function getBaseAttributes()
    {
        return array_keys($this->getConfigData('base_attributes'));
    }

    /**
     * Check whether debug mode is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function getIsDebug($storeId)
    {
        return (bool)$this->getConfigData('debug', $storeId);
    }

    /**
     * Returns all required attributes
     *
     * @return array
     */
    public function getRequiredAttributes()
    {
        $requiredAttributes = array();
        foreach ($this->getAttributes() as $group => $attributes) {
            foreach ($attributes as $attributeName => $attribute) {
                if ($attribute['required']) {
                    $requiredAttributes[$attributeName] = $attribute;
                }
            }
        }

        return $requiredAttributes;
    }
}
