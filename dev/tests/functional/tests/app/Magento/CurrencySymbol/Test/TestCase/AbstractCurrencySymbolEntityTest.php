<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CurrencySymbol\Test\TestCase;

use Magento\Mtf\Fixture\FixtureFactory;
use Magento\Mtf\TestCase\Injectable;
use Magento\Catalog\Test\Fixture\CatalogProductSimple;
use Magento\Config\Test\Page\Adminhtml\ConfigCurrencySetup;
use Magento\CurrencySymbol\Test\Page\Adminhtml\SystemCurrencyIndex;
use Magento\CurrencySymbol\Test\Page\Adminhtml\SystemCurrencySymbolIndex;

/**
 * Abstract class for currency symbol tests.
 */
abstract class AbstractCurrencySymbolEntityTest extends Injectable
{
    /**
     * Store>Config>General>CurrencyPage
     *
     * @var ConfigCurrencySetup
     */

    protected $ConfigCurrencySetup;
    /**
     * System Currency Symbol grid page.
     *
     * @var SystemCurrencySymbolIndex
     */
    protected $currencySymbolIndex;

    /**
     * System currency index page.
     *
     * @var SystemCurrencyIndex
     */
    protected $currencyIndex;

    /**
     * Fixture Factory.
     *
     * @var FixtureFactory
     */
    protected $fixtureFactory;

    /**
     * Create simple product and inject pages.
     * @param ConfigCurrencySetup $configCurrencySetup
     * @param SystemCurrencySymbolIndex $currencySymbolIndex
     * @param SystemCurrencyIndex $currencyIndex
     * @param FixtureFactory $fixtureFactory
     * @return array
     */
    public function __inject(
        ConfigCurrencySetup $configCurrencySetup,
        SystemCurrencySymbolIndex $currencySymbolIndex,
        SystemCurrencyIndex $currencyIndex,
        FixtureFactory $fixtureFactory
    ) {
        $this->ConfigCurrencySetup = $configCurrencySetup;
        $this->currencySymbolIndex = $currencySymbolIndex;
        $this->currencyIndex = $currencyIndex;
        $this->fixtureFactory = $fixtureFactory;
        $product = $this->fixtureFactory->createByCode(
            'catalogProductSimple',
            ['dataset' => 'product_with_category']
        );
        $product->persist();

        return ['product' => $product];
    }

    /**
     * Import currency rates.
     *
     * @param string $configData
     * @return void
     * @throws \Exception
     */
    protected function importCurrencyRate($configData)
    {
        $this->objectManager->getInstance()->create(
            'Magento\Config\Test\TestStep\SetupConfigurationStep',
            ['configData' => $configData]
        )->run();

        //Click 'Save Config' on 'Config>>Currency Setup' page.
        $this->ConfigCurrencySetup->open();
        $this->ConfigCurrencySetup->getFormPageActions()->save();

        // Import Exchange Rates for currencies
        $this->currencyIndex->open();
        $this->currencyIndex->getCurrencyRateForm()->clickImportButton();
        $this->currencyIndex->getCurrencyRateForm()->setCurrencyUSDUAHRate();
        if ($this->currencyIndex->getMessagesBlock()->isVisibleMessage('warning')) {
            throw new \Exception($this->currencyIndex->getMessagesBlock()->getWarningMessages());
        }
        $this->currencyIndex->getFormPageActions()->save();
    }

    /**
     * Disabling currency which has been added.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->objectManager->getInstance()->create(
            'Magento\Config\Test\TestStep\SetupConfigurationStep',
            ['configData' => 'config_currency_symbols_usd']
        )->run();
    }
}
