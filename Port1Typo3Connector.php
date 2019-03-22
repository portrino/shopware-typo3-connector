<?php
namespace Port1Typo3Connector;

use Port1Typo3Connector\Components\Api\Resource\Variant;
use Port1Typo3Connector\Components\ApiArticlesOrderNumberDecorator;
use Port1Typo3Connector\Components\ApiTokenDecorator;
use Port1Typo3Connector\Components\ApiUrlDecorator\ApiArticlesUrlDecorator;
use Port1Typo3Connector\Components\ApiUrlDecorator\ApiCategoriesUrlDecorator;
use Port1Typo3Connector\Components\ApiUrlDecorator\ApiVariantsUrlDecorator;
use Port1Typo3Connector\Service\Notification\Command;
use Port1Typo3Connector\Service\Notification\Typo3NotificationService;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Bundle\StoreFrontBundle\Struct\Media;
use Shopware\Components\Model\ModelEntity;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Kernel;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;
use Shopware\Models\Shop\Shop;

/**
 * Class Port1Typo3Connector
 *
 * @package Port1Typo3Connector
 */
class Port1Typo3Connector extends Plugin
{

    /**
     * @var array
     */
    static protected $apiEndpoints = [
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
    ];

    /**
     * @param \Shopware\Components\Plugin\Context\InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $this->addTypo3ApiUrlAttribute();
    }


    public static function getSubscribedEvents()
    {
        $result = [
            //            we don`t need to extendExtJS for now, because it is hard to place the attribute field within another form
            //            https://developers.shopware.com/developers-guide/attribute-system/#move-attribute-fields-into-anoth
            //            'Enlight_Controller_Action_PostDispatchSecure_Backend_UserManager' => 'extendExtJS'
            Article::class . '::postPersist' => 'createEntity',
            Article::class . '::postUpdate' => 'updateEntity',
            Article::class . '::preRemove' => 'deleteEntity',

            Category::class . '::postPersist' => 'createEntity',
            Category::class . '::preUpdate ' => 'preUpdateEntity',
            Category::class . '::postUpdate' => 'updateEntity',
            Category::class . '::preRemove' => 'deleteEntity',

            Media::class . '::postPersist' => 'createEntity',
            Media::class . '::postUpdate' => 'updateEntity',
            Media::class . '::preRemove' => 'deleteEntity',

            Shop::class . '::postPersist' => 'createEntity',
            Shop::class . '::postUpdate' => 'updateEntity',
            Shop::class . '::preRemove' => 'deleteEntity'
        ];

        /**
         * subscribe to init event for each endpoint controller of REST-API
         */
        foreach (self::$apiEndpoints as $apiEndpoint) {
            /**
             * subscribe init api to all endpoints
             */
            $result['Enlight_Controller_Action_Init_Api_' . ucfirst($apiEndpoint)][] = 'onInitApiAddToken';

            if (class_exists('Port1Typo3Connector\\Components\\ApiUrlDecorator\\Api' . ucfirst($apiEndpoint) . 'UrlDecorator')) {
                $result['Enlight_Controller_Action_PostDispatchSecure_Api_' . ucfirst($apiEndpoint)][] = [
                    'onApi' . ucfirst($apiEndpoint) . 'AddUrl'
                ];
            }

            if (class_exists('Port1Typo3Connector\\Components\\Api' . ucfirst($apiEndpoint) . 'OrderNumberDecorator')) {
                $result['Enlight_Controller_Action_PostDispatchSecure_Api_' . ucfirst($apiEndpoint)][] = [
                    'onApi' . ucfirst($apiEndpoint) . 'AddOrderNumber'
                ];
            }
        }

        return $result;
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function createEntity(\Enlight_Event_EventArgs $arguments)
    {
        /** @var ModelEntity|Article|Category|Media|Shop $entity */
        $entity = $arguments->get('entity');

        /** @var Typo3NotificationService $notificationService */
        $notificationService = $this->container->get('port1_typo3_connector.typo3_notification_service');
        $notificationService->notify(
            Command::COMMAND_CREATE,
            $entity
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function updateEntity(\Enlight_Event_EventArgs $arguments)
    {
        /** @var ModelEntity|Article|Category|Media|Shop $entity */
        $entity = $arguments->get('entity');

        /** @var Typo3NotificationService $notificationService */
        $notificationService = $this->container->get('port1_typo3_connector.typo3_notification_service');
        $notificationService->notify(
            Command::COMMAND_UPDATE,
            $entity
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function deleteEntity(\Enlight_Event_EventArgs $arguments)
    {
        /** @var ModelEntity|Article|Category|Media|Shop $entity */
        $entity = $arguments->get('entity');

        /** @var Typo3NotificationService $notificationService */
        $notificationService = $this->container->get('port1_typo3_connector.typo3_notification_service');
        $notificationService->notify(
            Command::COMMAND_DELETE,
            $entity
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onInitApiAddToken(\Enlight_Event_EventArgs $args)
    {
        $apiTokenDecorator = new ApiTokenDecorator($args->get('subject'));
        $apiTokenDecorator->addApiToken();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @throws \Exception
     */
    public function onApiArticlesAddUrl(\Enlight_Event_EventArgs $args)
    {
        $apiUrlDecorator = new ApiArticlesUrlDecorator($args->get('subject'), $this->container);
        $apiUrlDecorator->addUrl();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @throws \Exception
     */
    public function onApiCategoriesAddUrl(\Enlight_Event_EventArgs $args)
    {
        $apiUrlDecorator = new ApiCategoriesUrlDecorator($args->get('subject'), $this->container);
        $apiUrlDecorator->addUrl();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @throws \Exception
     */
    public function onApiVariantsAddUrl(\Enlight_Event_EventArgs $args)
    {
        $apiUrlDecorator = new ApiVariantsUrlDecorator($args->get('subject'), $this->container);
        $apiUrlDecorator->addUrl();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onApiArticlesAddOrderNumber(\Enlight_Event_EventArgs $args)
    {
        $apiOrderNumberDecorator = new ApiArticlesOrderNumberDecorator($args->get('subject'));
        $apiOrderNumberDecorator->addOrderNumber();
    }

    /**
     * add TYPO3 API-URL attribute to s_core_auth_attributes
     * @throws \Exception
     */
    protected function addTypo3ApiUrlAttribute()
    {
        /** @var \Shopware\Bundle\AttributeBundle\Service\CrudService $service */
        $service = $this->container->get('shopware_attribute.crud_service');

        $service->update('s_core_auth_attributes', 'typo3_api_url', TypeMapping::TYPE_STRING, [
            'label' => 'TYPO3 API-URL',
            'supportText' => 'Enter the TYPO3 API-URL here to push notifications about changes of articles / categories to this endpoint.',
            'helpText' => 'Enter the TYPO3 API-URL here',

            //user has the opportunity to translate the attribute field for each shop
            'translatable' => true,

            //attribute will be displayed in the backend module
            'displayInBackend' => true,

            //numeric position for the backend view, sorted ascending
            'position' => 100,

            //user can modify the attribute in the free text field module
            'custom' => false,

        ]);

        return true;
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     * @throws \Exception
     */
    public function extendExtJS(\Enlight_Event_EventArgs $arguments)
    {
        /** @var \Enlight_View_Default $view */
        $view = $arguments->get('subject')->View();
        $view->addTemplateDir($this->getPath() . '/Views/');
        $view->extendsTemplate('backend/port1_typo3_connector/view/user/create.js');
    }
}
