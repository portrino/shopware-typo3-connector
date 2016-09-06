<?php

namespace Port1Typo3Connector\Components;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
 */

use Shopware\Components\Api\Manager;
use Shopware\Components\Api\Resource\Shop;
use Shopware\Components\Routing\Context;
use Shopware\Components\Routing\Router;
use Shopware\Models\Shop\DetachedShop;

/**
 * Class ApiUrlDecorator
 *
 * @package Portrino\Typo3Connector\Components
 */
abstract class ApiUrlDecorator
{

    /**
     * @var \Shopware_Controllers_Api_Articles
     */
    protected $controller = null;

    /**
     * @var \Enlight_Controller_Request_Request
     */
    protected $request = null;

    /**
     * @var \Enlight_View_Default
     */
    protected $view = null;

    /**
     * @var Shop
     */
    protected $shopResource = null;

    /**
     * @var DetachedShop
     */
    protected $shop = null;

    /**
     * @var Context
     */
    protected $context = null;

    /**
     * @var bool
     */
    protected $isPxShopwareRequest = false;

    /**
     * ApiTokenDecorator constructor.
     *
     * @param \Enlight_Controller_Action $controller
     */
    public function __construct(\Enlight_Controller_Action $controller)
    {
        $this->controller = $controller;
        $this->request = $this->controller->Request();
        $this->view = $this->controller->View();
        $this->shopResource = Manager::getResource('shop');
        $this->isPxShopwareRequest = ($this->request->getParam('px_shopware') != null) ? (bool)$this->request->getParam('px_shopware') : false;

        if ($this->isPxShopwareRequest) {
            $language = ($this->request->getParam('language') != null) ? (int)$this->request->getParam('language') : false;
            if ($language != false) {
//                we could not use this query, because of bug described here: https://issues.shopware.com/#/issues/SW-15388
//                $this->shop = $this->shopResource->getRepository()->queryBy(array('active' => TRUE, 'locale' => $language))->getOneOrNullResult();
                $this->shop = $this->shopResource->getRepository()->queryBy([
                    'active' => true,
                    'id' => $language
                ])->getOneOrNullResult();
            } else {
                $this->shop = $this->shopResource->getRepository()->getActiveDefault();
            }
            $router = $this->controller->Front()->Router();

            if ($router instanceof Router) {
                $router->getContext()->setHost($this->shop->getHost());
                $router->getContext()->setBaseUrl($this->shop->getBaseUrl());
                $router->getContext()->setShopId($this->shop->getId());
                $router->getContext()->setSecure($this->shop->getSecure());
                $router->getContext()->setAlwaysSecure($this->shop->getAlwaysSecure());
                $router->getContext()->setSecureHost($this->shop->getSecureHost());
                $router->getContext()->setSecureBaseUrl($this->shop->getSecureBaseUrl());
            }
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function addUrl()
    {
        if ($this->isPxShopwareRequest) {
            try {
                $dataBefore = $this->controller->View()->getAssign('data');

                $data = $this->controller->View()->getAssign('data');
                $action = $this->controller->Request()->getActionName();

                if ($action === 'get') {
                    if (is_array($data) && isset($data['id'])) {
                        $url = $this->getItemUrl($data['id']);
                        $data = array_merge_recursive($this->controller->View()->getAssign('data'),
                            ['pxShopwareUrl' => $url]);
                    }
                }

                if ($action === 'index') {
                    if (is_array($data)) {
                        // add article urls to each article of the list
                        $items = $this->controller->View()->getAssign('data');
                        foreach ($items as $key => $item) {
                            $item['pxShopwareUrl'] = $this->getItemUrl($item['id']);
                            $items[$key] = $item;
                        }
                        $data = $items;
                    }

                }

                $this->controller->View()->clearAssign('data');
                $this->controller->View()->assign('data', $data);

                /**
                 * we have to call postDispatch again to force Zend_Json::encode call
                 */
                $this->controller->postDispatch();

            } catch (\Exception $exception) {
                /**
                 * in case of an error we should reset data and render view again with data before
                 */
                $this->controller->View()->clearAssign('data');
                $this->controller->View()->assign('data', $dataBefore);
                $this->controller->postDispatch();
            }
        }
    }

    /**
     * @param int $itemId
     *
     * @return string
     */
    abstract protected function getItemUrl($itemId);


}