<?php
namespace Port1Typo3Connector\Components;

use Shopware\Components\Api\Resource\Shop;
use Shopware\Components\Routing\Context;
use Shopware\Components\Routing\Router;
use Shopware\Models\Shop\DetachedShop;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApiUrlDecorator
 *
 * @package Portrino\Typo3Connector\Components
 */
abstract class ApiUrlDecorator
{

    /**
     * @var \Shopware_Components_Config
     */
    protected $config;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Shopware_Controllers_Api_Articles
     */
    protected $controller;

    /**
     * @var \Enlight_Controller_Request_Request
     */
    protected $request;

    /**
     * @var \Enlight_View_Default
     */
    protected $view;

    /**
     * @var Shop
     */
    protected $shopResource;

    /**
     * @var DetachedShop
     */
    protected $shop;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var bool
     */
    protected $isPxShopwareRequest = false;

    /**
     * ApiTokenDecorator constructor.
     *
     * @param \Enlight_Controller_Action $controller
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(\Enlight_Controller_Action $controller, ContainerInterface $container)
    {
        $this->container = $container;
        $this->controller = $controller;

        $this->config = $this->container->get('config');
        $this->request = $this->controller->Request();
        $this->view = $this->controller->View();
        $this->shopResource = $this->container->get('shopware.api.shop');
        $this->isPxShopwareRequest = ($this->request->getParam('px_shopware') !== null) ? (bool)$this->request->getParam('px_shopware') : false;

        if ($this->isPxShopwareRequest) {
            $language = ($this->request->getParam('language') !== null) ? (int)$this->request->getParam('language') : false;
            if ($language !== false) {
                $this->shop = $this->shopResource->getRepository()->findOneBy([
                    'active' => true,
                    'id' => $language
                ]);
            } else {
                $this->shop = $this->shopResource->getRepository()->getActiveDefault();
            }
            $router = $this->controller->Front()->Router();

            if ($router instanceof Router) {
                $context = $router->getContext();
                $newContext = Context::createFromShop($this->shop, $this->config);
                // Reuse the host
                if ($newContext->getHost() === null) {
                    $newContext->setHost($context->getHost());
                    $newContext->setBaseUrl($this->shop->getBaseUrl() ?: $context->getBaseUrl());
                    $newContext->setSecure($this->shop->getSecure() ?: $context->isSecure());
                }
                // Reuse the global params like controller and action
                $globalParams = $context->getGlobalParams();
                $newContext->setGlobalParams($globalParams);
                // Check baseUrl
                $newContext->setBaseUrl(
                    $newContext->getBaseUrl() ?: $this->shop->getBasePath()
                );
                $router->setContext($newContext);
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
                        $data = array_merge_recursive(
                            $this->controller->View()->getAssign('data'),
                            ['pxShopwareUrl' => $url]
                        );
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
     * @param string $orderNumber
     *
     * @return string
     */
    abstract protected function getItemUrl($itemId, $orderNumber = null);
}
