<?php

namespace Portrino\Typo3Connector\Components;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
 */

use Shopware\Components\Api\Manager;

/**
 * Class ApiUrlDecorator
 *
 * @package Portrino\Typo3Connector\Components
 */
abstract class ApiUrlDecorator {

    /**
     * @var \Shopware_Controllers_Api_Articles
     */
    protected $controller = NULL;

    /**
     * @var \Enlight_Controller_Request_Request
     */
    protected $request = NULL;

    /**
     * @var \Enlight_View_Default
     */
    protected $view = NULL;

    /**
     * @var \Shopware\Components\Api\Resource\Shop
     */
    protected $shopResource = NULL;

    /**
     * @var \Shopware\Models\Shop\DetachedShop
     */
    protected $shop = NULL;

    /**
     * @var bool
     */
    protected $isPxShopwareRequest = FALSE;

    /**
     * ApiTokenDecorator constructor.
     *
     * @param \Enlight_Controller_Action $controller
     */
    public function __construct(\Enlight_Controller_Action $controller) {
        $this->controller = $controller;
        $this->request = $this->controller->Request();
        $this->view = $this->controller->View();
        $this->shopResource = Manager::getResource('shop');
        $this->isPxShopwareRequest = ($this->request->getParam('px_shopware') != NULL) ? (bool)$this->request->getParam('px_shopware') : FALSE;

        if ($this->isPxShopwareRequest) {
            $language = ($this->request->getParam('language') != NULL) ? (int)$this->request->getParam('language') : FALSE;
            if ($language != FALSE) {
//                we could not use this query, because of bug described here: https://issues.shopware.com/#/issues/SW-15388
//                $this->shop = $this->shopResource->getRepository()->queryBy(array('active' => TRUE, 'locale' => $language))->getOneOrNullResult();
                $this->shop = $this->shopResource->getRepository()->queryBy(array('active' => TRUE, 'id' => $language))->getOneOrNullResult();
            } else {
                $this->shop = $this->shopResource->getRepository()->getActiveDefault();
            }
            $router = $this->controller->Front()->Router();



            if ($router instanceof \Shopware\Components\Routing\Router) {
                $router->getContext()->setShopId($this->shop->getId());
                $router->getContext()->setBaseUrl($this->shop->getBaseUrl());
            }
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function addPxShopwareUrl() {
        if ($this->isPxShopwareRequest) {
            try {
                $dataBefore =  $this->controller->View()->getAssign('data');

                $data = $this->controller->View()->getAssign('data');
                $action = $this->controller->Request()->getActionName();

                if ($action === 'get') {
                    if (is_array($data) && isset($data['id'])) {
                        $url = $this->getItemUrl($data['id']);
                        $data = array_merge_recursive($this->controller->View()->getAssign('data'), array('pxShopwareUrl' => $url));
                    }
                }
                
                if ($action === 'index') {
                    if (is_array($data)) {
                        // add article urls to each article of the list
                        $items = $this->controller->View()->getAssign('data');
                        foreach($items as $key => $item) {
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
     * @return string
     */
    abstract protected function getItemUrl($itemId);


}