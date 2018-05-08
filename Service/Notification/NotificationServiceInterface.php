<?php

namespace Port1Typo3Connector\Service\Notification;

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