<?php
namespace Port1Typo3Connector;

use Port1Typo3Connector\Components\ApiArticlesOrderNumberDecorator;
use Port1Typo3Connector\Components\ApiTokenDecorator;
use Port1Typo3Connector\Components\ApiUrlDecorator\ApiArticlesUrlDecorator;
use Port1Typo3Connector\Components\ApiUrlDecorator\ApiCategoriesUrlDecorator;
use Port1Typo3Connector\Service\Notification\Command;
use Port1Typo3Connector\Service\Notification\NotificationService;
use Port1Typo3Connector\Service\Notification\Typo3NotificationService;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Bundle\PluginInstallerBundle\Service\PluginLicenceService;
use Shopware\Bundle\StoreFrontBundle\Struct\Media;
use Shopware\Components\Model\ModelEntity;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;
use Shopware\Models\Shop\Shop;

class Port1Typo3Connector extends Plugin
{

    /**
     * @var array
     */
    static protected $apiEndpoints = array(
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
     * @param \Shopware\Components\Plugin\Context\InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $this->checkLicense();

        $this->addTypo3ApiUrlAttribute();

    }


    public static function getSubscribedEvents()
    {
        $result = [
            //            we don`t need to extendExtJS for now, because it is hard to place the attribute field within another form
            //            https://developers.shopware.com/developers-guide/attribute-system/#move-attribute-fields-into-anoth
            //            'Enlight_Controller_Action_PostDispatchSecure_Backend_UserManager' => 'extendExtJS'
            \Shopware\Models\Article\Article::class . '::postPersist' => 'createEntity',
            \Shopware\Models\Article\Article::class . '::postUpdate' => 'updateEntity',
            \Shopware\Models\Article\Article::class . '::preRemove' => 'deleteEntity',

            \Shopware\Models\Category\Category::class . '::postPersist' => 'createEntity',
            \Shopware\Models\Category\Category::class . '::postUpdate' => 'updateEntity',
            \Shopware\Models\Category\Category::class . '::preRemove' => 'deleteEntity',

            \Shopware\Models\Media\Media::class . '::postPersist' => 'createEntity',
            \Shopware\Models\Media\Media::class . '::postUpdate' => 'updateEntity',
            \Shopware\Models\Media\Media::class . '::preRemove' => 'deleteEntity',

            \Shopware\Models\Shop\Shop::class . '::postPersist' => 'createEntity',
            \Shopware\Models\Shop\Shop::class . '::postUpdate' => 'updateEntity',
            \Shopware\Models\Shop\Shop::class . '::preRemove' => 'deleteEntity'
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
    public function createEntity(\Enlight_Event_EventArgs $arguments) {
        /** @var ModelEntity|Article|Category|Media|Shop $entity */
        $entity = $arguments->get('entity');
        $reflection = new \ReflectionClass(get_class($entity));
        $type = strtolower($reflection->getShortName());

        /** @var Typo3NotificationService $notificationService */
        $notificationService = $this->container->get('port1_typo3_connector.typo3_notification_service');
        $notificationService->notify(
            Command::COMMAND_CREATE,
            $type,
            $entity->getId()
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function updateEntity(\Enlight_Event_EventArgs $arguments) {
        /** @var ModelEntity|Article|Category|Media|Shop $entity */
        $entity = $arguments->get('entity');
        $reflection = new \ReflectionClass(get_class($entity));
        $type = strtolower($reflection->getShortName());

        /** @var Typo3NotificationService $notificationService */
        $notificationService = $this->container->get('port1_typo3_connector.typo3_notification_service');
        $notificationService->notify(
            Command::COMMAND_UPDATE,
            $type,
            $entity->getId()
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function deleteEntity(\Enlight_Event_EventArgs $arguments) {
        /** @var ModelEntity|Article|Category|Media|Shop $entity */
        $entity = $arguments->get('entity');
        $reflection = new \ReflectionClass(get_class($entity));
        $type = strtolower($reflection->getShortName());

        /** @var Typo3NotificationService $notificationService */
        $notificationService = $this->container->get('port1_typo3_connector.typo3_notification_service');
        $notificationService->notify(
            Command::COMMAND_DELETE,
            $type,
            $entity->getId()
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onInitApiAddToken(\Enlight_Event_EventArgs $args)
    {
        $apiTokenDecorator = new ApiTokenDecorator($args->get('subject'));
        return $apiTokenDecorator->addApiToken();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onApiArticlesAddUrl(\Enlight_Event_EventArgs $args)
    {
        $apiUrlDecorator = new ApiArticlesUrlDecorator($args->get('subject'));
        return $apiUrlDecorator->addUrl();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onApiCategoriesAddUrl(\Enlight_Event_EventArgs $args)
    {
        $apiUrlDecorator = new ApiCategoriesUrlDecorator($args->get('subject'));
        return $apiUrlDecorator->addUrl();
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onApiArticlesAddOrderNumber(\Enlight_Event_EventArgs $args)
    {
        $apiOrderNumberDecorator = new ApiArticlesOrderNumberDecorator($args->get('subject'));
        return $apiOrderNumberDecorator->addOrderNumber();
    }

    /**
     * add TYPO3 API-URL attribute to s_core_auth_attributes
     */
    protected function addTypo3ApiUrlAttribute()
    {
        $service = $this->container->get('shopware_attribute.crud_service');

        $service->update('s_core_auth_attributes', 'typo3_api_url', TypeMapping::TYPE_STRING, [
            'label' => 'TYPO3 API-URLhttp://typo3.local.192.168.1.246.nip.io/?type=1471426941',
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
     */
    public function extendExtJS(\Enlight_Event_EventArgs $arguments)
    {
        /** @var \Enlight_View_Default $view */
        $view = $arguments->getSubject()->View();
        $view->addTemplateDir($this->getPath() . '/Views/');
        $view->extendsTemplate('backend/port1_typo3_connector/view/user/create.js');
    }

    /**
     * checkLicense()-method for Port1Typo3Connector
     */
    public function checkLicense($throwException = true)
    {
        /** @var \Shopware $application */
        $application = $this->container->get('application');

        if ($application->Environment() === 'dev' ||
            $application->Environment() === 'staging'
        ) {
            return true;
        }

        if (!$application->Container()->has('license')) {
            if ($throwException) {
                throw new \Exception('The license manager has to be installed and active');
            } else {
                return false;
            }
        }

        try {
            static $r, $module = 'Port1Typo3Connector';
            if (!isset($r)) {
                $s = base64_decode('zkFJGvtiUOjC2mLx2oGm+nXWV38=');
                $c = base64_decode('j1/FmuiYqRPoptzEjxSF7CZ6HjY=');
                $r = sha1(uniqid('', true), true);
                /** @var $l Shopware_Components_License */
                $l = $application->License();
                $i = $l->getLicense($module, $r);
                $t = $l->getCoreLicense();
                $u = strlen($t) === 20 ? sha1($t . $s . $t, true) : 0;
                $r = $i === sha1($c . $u . $r, true);
            }
            if (!$r && $throwException) {
                throw new Exception('License check for module "' . $module . '" has failed.');
            }
            return $r;
        } catch (\Exception $e) {
            if ($throwException) {
                throw new Exception('License check for module "' . $module . '" has failed.');
            } else {
                return false;
            }
        }
    }
}
