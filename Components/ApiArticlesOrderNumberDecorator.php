<?php

namespace Port1Typo3Connector\Components;

use Shopware\Components\Api\Manager;
use Shopware\Components\Model\QueryBuilder;

/**
 * Class ApiArticlesOrderNumberDecorator
 *
 * @package Portrino\Typo3Connector\Components
 */
class ApiArticlesOrderNumberDecorator
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
     * @var bool
     */
    protected $isPxShopwareRequest = false;

    /**
     * @var Shopware\Components\Api\Resource\Article
     */
    protected $resource = null;

    /**
     * ApiTokenDecorator constructor.
     *
     * @param \Enlight_Controller_Action $controller
     */
    public function __construct(\Enlight_Controller_Action $controller)
    {
        $this->controller = $controller;
        $this->request = $this->controller->Request();
        $this->isPxShopwareRequest = ($this->request->getParam('px_shopware') != null) ? (bool)$this->request->getParam('px_shopware') : false;

        if ($this->isPxShopwareRequest) {
            $this->resource = Manager::getResource('article');
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function addOrderNumber()
    {
        if ($this->isPxShopwareRequest) {
            try {
                $dataBefore = $this->controller->View()->getAssign('data');
                $data = $this->controller->View()->getAssign('data');
                $action = $this->controller->Request()->getActionName();
                if ($action === 'get') {
                    if (is_array($data) && isset($data['id'])) {
                        if (isset($data['mainDetail']['number'])) {
                            $orderNumber = $data['mainDetail']['number'];
                        } else {
                            $orderNumber = $this->getOrderNumber($data);
                        }
                        $data = array_merge_recursive($this->controller->View()->getAssign('data'),
                            ['pxShopwareOrderNumber' => $orderNumber]);
                    }
                }

                if ($action === 'index') {
                    if (is_array($data)) {
                        // add article urls to each article of the list
                        $items = $this->controller->View()->getAssign('data');
                        foreach ($items as $key => $item) {
                            $item['pxShopwareOrderNumber'] = $this->getOrderNumber($item);
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
     * @param array $article
     *
     * @return string
     */
    protected function getOrderNumber($article)
    {
        try {
            /** @var \Doctrine\ORM\QueryBuilder|QueryBuilder $builder */
            $builder = $this->resource->getManager()->createQueryBuilder();

            $builder->select(['details'])
                    ->from('Shopware\Models\Article\Detail', 'details')
                    ->where('details.id = :mainDetailId')
                    ->setParameter('mainDetailId', $article['mainDetailId']);

            /** @var $detail \Shopware\Models\Article\Detail */
            $detail = $builder->getQuery()->getOneOrNullResult();

            if ($detail != null) {
                return $detail->getNumber();
            }

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Internal helper function to get access to the article repository.
     *
     * @return Shopware\Models\Article\Repository
     */
    protected function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = Shopware()->Models()->getRepository('Shopware\Models\Article\Article');
        }

        return $this->repository;
    }

}