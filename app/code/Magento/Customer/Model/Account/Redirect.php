<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Model\Account;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Redirect
{
    /** URL to redirect user on successful login or registration */
    const LOGIN_REDIRECT_URL = 'login_redirect';

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @param RequestInterface $request
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     * @param DecoderInterface $urlDecoder
     * @param CustomerUrl $customerUrl
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        RequestInterface $request,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        DecoderInterface $urlDecoder,
        CustomerUrl $customerUrl,
        ResultFactory $resultFactory
    ) {
        $this->request = $request;
        $this->session = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->urlDecoder = $urlDecoder;
        $this->customerUrl = $customerUrl;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Retrieve redirect
     *
     * @return ResultRedirect|ResultForward
     */
    public function getRedirect()
    {
        $this->updateLastCustomerId();
        $this->prepareRedirectUrl();

        /** @var ResultRedirect|ResultForward $result */
        if ($this->session->getBeforeRequestParams()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->setParams($this->session->getBeforeRequestParams())
                ->setModule($this->session->getBeforeModuleName())
                ->setController($this->session->getBeforeControllerName())
                ->forward($this->session->getBeforeAction());
        } else {
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setUrl($this->session->getBeforeAuthUrl(true));
        }
        return $result;
    }

    /**
     * Update last customer id, if required
     *
     * @return void
     */
    protected function updateLastCustomerId()
    {
        $lastCustomerId = $this->session->getLastCustomerId();
        if (isset($lastCustomerId)
            && $this->session->isLoggedIn()
            && $lastCustomerId != $this->session->getId()
        ) {
            $this->session->unsBeforeAuthUrl()
                ->setLastCustomerId($this->session->getId());
        }
    }

    /**
     * Prepare redirect URL
     *
     * @return void
     */
    protected function prepareRedirectUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        $url = $this->session->getBeforeAuthUrl();
        if (!$url) {
            $url = $baseUrl;
        }

        switch ($url) {
            case $baseUrl:
                if ($this->session->isLoggedIn()) {
                    $this->processLoggedCustomer();
                } else {
                    $this->applyRedirect($this->customerUrl->getLoginUrl());
                }
                break;

            case $this->customerUrl->getLogoutUrl():
                $this->applyRedirect($this->customerUrl->getDashboardUrl());
                break;

            default:
                if (!$this->session->getAfterAuthUrl()) {
                    $this->session->setAfterAuthUrl($this->session->getBeforeAuthUrl());
                }
                if ($this->session->isLoggedIn()) {
                    $this->applyRedirect($this->session->getAfterAuthUrl(true));
                }
                break;
        }
    }

    /**
     * Prepare redirect URL for logged in customer
     *
     * Redirect customer to the last page visited after logging in.
     *
     * @return void
     */
    protected function processLoggedCustomer()
    {
        // Set default redirect URL for logged in customer
        $this->applyRedirect($this->customerUrl->getAccountUrl());

        if (!$this->scopeConfig->isSetFlag(
            CustomerUrl::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD,
            ScopeInterface::SCOPE_STORE
        )
        ) {
            $referer = $this->request->getParam(CustomerUrl::REFERER_QUERY_PARAM_NAME);
            if ($referer) {
                $referer = $this->urlDecoder->decode($referer);
                if ($this->url->isOwnOriginUrl()) {
                    $this->applyRedirect($referer);
                }
            }
        } elseif ($this->session->getAfterAuthUrl()) {
            $this->applyRedirect($this->session->getAfterAuthUrl(true));
        }
    }

    /**
     * Prepare redirect URL
     *
     * @param string $url
     * @return void
     */
    private function applyRedirect($url)
    {
        $this->session->setBeforeAuthUrl($url);
    }

    /**
     * Get Cookie manager. For release backward compatibility.
     *
     * @deprecated
     * @return CookieManagerInterface
     */
    protected function getCookieManager()
    {
        if (!is_object($this->cookieManager)) {
            $this->cookieManager = ObjectManager::getInstance()->get(CookieManagerInterface::class);
        }
        return $this->cookieManager;
    }

    /**
     * Set cookie manager. For unit tests.
     *
     * @deprecated
     * @param object $value
     * @return void
     */
    public function setCookieManager($value)
    {
        $this->cookieManager = $value;
    }

    /**
     * Get redirect route from cookie for case of successful login/registration
     *
     * @return null|string
     */
    public function getRedirectCookie()
    {
        return $this->getCookieManager()->getCookie(self::LOGIN_REDIRECT_URL, null);
    }

    /**
     * Save redirect route to cookie for case of successful login/registration
     *
     * @param string $route
     * @return void
     */
    public function setRedirectCookie($route)
    {
        $this->getCookieManager()->setPublicCookie(self::LOGIN_REDIRECT_URL, $route);
    }

    /**
     * Clear cookie with requested route
     *
     * @return void
     */
    public function clearRedirectCookie()
    {
        $this->getCookieManager()->deleteCookie(self::LOGIN_REDIRECT_URL);
    }
}
