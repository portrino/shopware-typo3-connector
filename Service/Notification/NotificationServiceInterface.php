<?php

namespace Port1Typo3Connector\Service\Notification;

use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;
use Shopware\Models\Media\Media;
use Shopware\Models\Shop\Shop;

/**
 * Interface NotificationServiceInterface
 *
 * @package Port1Typo3Connector\Service\Notification
 */
interface NotificationServiceInterface
{

    /**
     * sends the notification to the consumer system
     *
     * @param string $action
     * @param ModelEntity|Article|Category|Media|Shop $entity
     */
    public function notify($action, $entity);
}
