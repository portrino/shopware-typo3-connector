<?php

namespace Port1Typo3Connector\Service\Notification;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
 */

use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;
use Shopware\Models\Media\Media;
use Shopware\Models\Shop\Shop;

/**
 * Class NotificationService
 *
 * @package Port1Typo3Connector\Service\Notification
 */
class Command
{

    const COMMAND_CREATE = 'create';

    const COMMAND_UPDATE = 'update';

    const COMMAND_DELETE = 'delete';

    const TYPE_ARTICLE = 'article';

    const TYPE_CATEGORY = 'category';

    const TYPE_MEDIA = 'media';

    const TYPE_SHOP = 'shop';

    const TYPE_VERSION = 'version';

    /**
     * @var string
     */
    public $action = '';

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var string
     */
    public $id = '';

    /**
     * Command constructor.
     *
     * @param string $action
     * @param string $type
     * @param string $id
     */
    public function __construct($action, $type, $id)
    {
        $this->action = $action;
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param Article|Category|Media|Shop $entity
     *
     * @return string
     */
    public static function getTypeFromEntity($entity)
    {
        $result = '';
        /** @var \ReflectionClass $reflection */
        $reflection = new \ReflectionClass(get_class($entity));
        $type = strtolower($reflection->getShortName());

        return $type;
    }
}