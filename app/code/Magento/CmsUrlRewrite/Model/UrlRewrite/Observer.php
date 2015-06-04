<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsUrlRewrite\Model\UrlRewrite;

use Magento\CmsUrlRewrite\Model\Mode\CmsPage as CmsPageMode;
use Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\UrlRewrite\Model\UrlRewrite;

class Observer
{
    /**
     * @var \Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator
     */
    protected $cmsPageUrlPathGenerator;
    /**
     * @var \Magento\CmsUrlRewrite\Model\Mode\CmsPage
     */
    protected $cmsPageMode;

    /**
     * @param CmsPageMode $cmsPageMode
     * @param CmsPageUrlPathGenerator $cmsPageUrlPathGenerator
     */
    public function __construct(
        CmsPageMode $cmsPageMode,
        CmsPageUrlPathGenerator $cmsPageUrlPathGenerator
    )
    {
        $this->cmsPageMode             = $cmsPageMode;
        $this->cmsPageUrlPathGenerator = $cmsPageUrlPathGenerator;
    }

    /**
     * @param EventObserver $observer
     */
    public function handleUrlRewriteSave(EventObserver $observer)
    {
        /** @var \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite */
        $urlRewrite = $observer->getEvent()->getUrlRewrite();
        $cmsPage = $this->getCmsPage($urlRewrite);
        if ($cmsPage->getId()) {
            if ($urlRewrite->isObjectNew()) {
                $urlRewrite->setEntityType(CmsPageMode::ENTITY_TYPE)->setEntityId($cmsPage->getId());
            }
            if ($urlRewrite->getRedirectType() && !$urlRewrite->getIsAutogenerated()) {
                $targetPath = $this->cmsPageUrlPathGenerator->getUrlPath($cmsPage);
            } else {
                $targetPath = $this->cmsPageUrlPathGenerator->getCanonicalUrlPath($cmsPage);
            }
            $urlRewrite->setTargetPath($targetPath);
        }
    }

    /**
     * @param UrlRewrite $urlRewrite
     * @return \Magento\Cms\Model\Page
     */
    protected function getCmsPage(UrlRewrite $urlRewrite)
    {
        return $this->cmsPageMode->getCmsPage($urlRewrite);
    }
}
