<?php
use Portrino\Typo3Connector\Components\ApiTokenDecorator;
use Portrino\Typo3Connector\Components\ApiUrlDecorator\ApiArticlesUrlDecorator;
use Portrino\Typo3Connector\Components\ApiUrlDecorator\ApiCategoriesUrlDecorator;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
 */

class Shopware_Plugins_Frontend_Port1Typo3Connector_Bootstrap extends Shopware_Components_Plugin_Bootstrap {

    /**
     * @var Shopware\Components\Api\Resource\Article
     */
    protected $resource = NULL;

    protected $apiEndpoints = array(
        0 => 'Articles',
        1 => 'Caches',
        2 => 'Categories',
        3 => 'Customers',
        4 => 'CustomerGroups',
        5 => 'Media',
        6 => 'Orders',
        7 => 'PropertyGroups',
        8 => 'Shops',
        9 => 'Translations',
        10 => 'Variants',
        11 => 'Version',

    );

    /**
     * Return the version of the plugin.
     *
     * @return mixed
     * @throws Exception
     */
    public function getVersion() {
        return '1.0.2';
    }

    /**
     * Return the label of the plugin
     *
     * @return string
     */
    public function getLabel() {
        return 'TYPO3-Connector';
    }

    /**
     * Returns plugin info
     *
     * @return array
     */
    public function getInfo() {
        return array(
            'version' => $this->getVersion(),
            'autor' => 'portrino GmbH',
            'copyright' => '© 2016 ',
            'label' => $this->getLabel(),
            'source' => 'Community',
            'description' => 'Enables communication with TYPO3-Extension "PxShopware".',
            'license' => '',
            'support' => 'info@portrino.de',
            'link' => 'http://www.portrino.de'
        );
    }

    /**
     * Register Service + an example controller PreDispatch method
     *
     * @return bool
     */
    public function install() {
        $this->subscribeEvents();

        return TRUE;
    }

    /**
     * Is executed after the collection has been added.
     */
    public function afterInit() {
        parent::afterInit();
        $this->Application()->Loader()->registerNamespace('Portrino\\Typo3Connector', $this->Path());
    }


    /**
     * subscribe events
     */
    private function subscribeEvents() {
        /**
         * subscribe to init event for each endpoint controller of REST-API
         */
        foreach ($this->apiEndpoints as $apiEndpoint) {
            $this->subscribeEvent('Enlight_Controller_Action_Init_Api_' . ucfirst($apiEndpoint), 'onInitApi');
            if (class_exists('\Portrino\Typo3Connector\Components\ApiUrlDecorator\Api' . ucfirst($apiEndpoint) . 'UrlDecorator')) {
                $this->subscribeEvent('Enlight_Controller_Action_PostDispatchSecure_Api_' . ucfirst($apiEndpoint), 'onApi' . ucfirst($apiEndpoint) . 'PostDispatchSecure');
            }
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onInitApi(\Enlight_Event_EventArgs $args) {
        /** @var ApiTokenDecorator $apiTokenDecorator */
        $apiTokenDecorator = new ApiTokenDecorator($args->get('subject'));
        return $apiTokenDecorator->addPxShopwareApiToken();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onApiArticlesPostDispatchSecure(\Enlight_Event_EventArgs $args) {
        $apiUrlDecorator = new ApiArticlesUrlDecorator($args->get('subject'));
        return $apiUrlDecorator->addPxShopwareUrl();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onApiCategoriesPostDispatchSecure(\Enlight_Event_EventArgs $args) {
        $apiUrlDecorator = new ApiCategoriesUrlDecorator($args->get('subject'));
        return $apiUrlDecorator->addPxShopwareUrl();
    }

}
