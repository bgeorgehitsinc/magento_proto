<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Stdlib\DateTime\Filter;


use Magento\Framework\Locale\Resolver;

/**
 * Date/Time filter. Converts datetime from localized to internal format.
 *
 * @api
 */
class DateTime extends Date
{
    /**
     * @var Resolver
    */
    private $localeResolver;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param Resolver $localeResolver
     *
     */
    public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate, Resolver $localeResolver)
    {
        parent::__construct($localeDate);
        $this->localeResolver = $localeResolver;
        $this->_localToNormalFilter = new \Zend_Filter_LocalizedToNormalized(
            [
                'date_format' => $this->_localeDate->getDateTimeFormat(
                    \IntlDateFormatter::SHORT
                ),
            ]
        );
        $this->_normalToLocalFilter = new \Zend_Filter_NormalizedToLocalized(
            ['date_format' => \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT]
        );
    }

    /**
     * Convert date from localized to internal format
     *
     * @param string $value
     * @return string
     * @throws \Exception
     * @since 100.1.0
     */
    public function filter($value)
    {
        $currentLocaleCode = $this->localeResolver->getLocale(); //retruning this value zh_Hans_CN, but we need zh_CN for date format
        if(strlen($currentLocaleCode>5)){
            $languageCode = explode('_', $currentLocaleCode);
            $useCode = $languageCode[0].'_'.$languageCode[2];
        }
        else{
            $useCode = $currentLocaleCode;
        }

        try {
            $value = $this->_localeDate->formatDateTime($value, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT, $useCode, null, null);
            $dateTime = $this->_localeDate->date($value, null, false);
            return $dateTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            throw new \Exception("Invalid input datetime format of value '$value'", $e->getCode(), $e);
        }
    }
}
