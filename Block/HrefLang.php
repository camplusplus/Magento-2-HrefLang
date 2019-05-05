<?php
/**
 * A Magento 2 module named Camplus2/HrefLang
 * Copyright (C) 2018
 *
 * This file is part of Camplus2/HrefLang.
 *
 * Camplus2/HrefLang is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Camplus2\HrefLang\Block;

use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\ResourceModel\Page;
use Magento\Store\Model\Group;
use Magento\Store\Model\Website;
use Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator;
use Magento\TestFramework\Inspection\Exception;

class HrefLang extends Template
{
    /**
     * @var CmsPageUrlPathGenerator
     */
    private $cmsPageUrlPathGenerator;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var PageResource
     */
    private $pageResource;

    /**
     * @var Http
     */
    private $request;

    /**
     * HrefLang constructor.
     * @param Template\Context $context
     * @param CmsPageUrlPathGenerator $cmsPageUrlPathGenerator
     * @param Page $pageResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        Http $request,
        CmsPageUrlPathGenerator $cmsPageUrlPathGenerator,
        PageRepositoryInterface $pageRepository,
        Page $pageResource,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->cmsPageUrlPathGenerator = $cmsPageUrlPathGenerator;
        $this->pageRepository = $pageRepository;
        $this->pageResource = $pageResource;
        $this->request = $request;
    }

    /**
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAlternate()
    {
        $alternate = [];
        foreach ($this->getStores() as $store) {
            $url = $this->getStoreUrl($store);
            if ($url) {
                $alternate[$this->getLocaleCode($store)] = $url;
            }
        }
        return (count($alternate) > 1) ? $alternate : null;
    }

    /**
     * @param $store
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStoreUrl($store)
    {
        switch ($this->request->getFullActionName()) {
            case 'cms_page_view':
                    return $this->getCmsPageUrl($this->request->getParam('page_id'), $store);
            case 'cms_index_index':
                return $store->getBaseUrl();
        }
        return '';
    }

    /**
     * @param $id
     * @param $store
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCmsPageUrl($id, $store)
    {
        $page = $this->pageRepository->getById($id);
        $pageId = $this->pageResource->checkIdentifier($page->getIdentifier(), $store->getId());
        if ($pageId) {
            $storePage = $this->pageRepository->getById($pageId);
            $path = $this->cmsPageUrlPathGenerator->getUrlPath($storePage);
            return $store->getBaseUrl() . $path;
        }
    }

    /**
     * @param $store
     * @return mixed
     */
    private function getLocaleCode($store)
    {
        $localeCode = $this->_scopeConfig->getValue('general/locale/code', 'stores', $store->getId());
        return str_replace('_', '-', strtolower($localeCode));
    }

    /**
     * @return array|StoreInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStores()
    {
        if ($this->_scopeConfig->isSetFlag('seo/hreflang/same_website_only')) {
            return $this->getSameWebsiteStores();
        }
        return $this->_storeManager->getStores();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSameWebsiteStores()
    {
        $stores = [];
        /** @var Website $website */
        $website = $this->_storeManager->getWebsite();
        foreach ($website->getGroups() as $group) {
            /** @var Group $group */
            foreach ($group->getStores() as $store) {
                $stores[] = $store;
            }
        }
        return $stores;
    }
}
