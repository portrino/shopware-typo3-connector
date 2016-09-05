<?php

namespace Port1Typo3Connector\Service\Notification;

    /**
     * Copyright (C) portrino GmbH - All Rights Reserved
     * Unauthorized copying of this file, via any medium is strictly prohibited
     * Proprietary and confidential
     * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
     */
use Shopware\Models\User\User;

/**
 * Class NotificationService
 *
 * @package Port1Typo3Connector\Service\Notification
 */
abstract class NotificationService implements NotificationServiceInterface
{

    /**
     * @var \Shopware\Models\User\Repository
     */
    protected $userRepository = null;

    /**
     * @var \Shopware\Models\User\User
     */
    protected $user = null;

    /**
     * @var string
     */
    protected $consumerApiUrl = false;

    /**
     * @var string
     */
    protected $apiKey = false;

    /**
     * NotificationService constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * initialize the service
     */
    protected function initialize() {
        $id = Shopware()->Container()->get('auth')->getIdentity()->id;
        $this->user = $this->getUserRepository()->getUserDetailQuery($id)->getOneOrNullResult();

        $this->apiKey = $this->getApiKey() != '' ? $this->getApiKey() : false;
        $this->consumerApiUrl = $this->getConsumerApiUrl() != '' ? $this->getConsumerApiUrl() : false;
    }

    /**
     * @return string
     */
    protected function getApiKey() {
        $result = '';
        if ($this->user && $this->user instanceof User) {
            $result = $this->user->getApiKey();
        }
        return $result;
    }

    /**
     * @return string
     */
    abstract protected function getConsumerApiUrl();

    /**
     * Helper function to get access to the user repository.
     *
     * @return \Shopware\Models\User\Repository
     */
    private function getUserRepository()
    {
        if ($this->userRepository === null) {
            $this->userRepository = Shopware()->Models()->getRepository('Shopware\Models\User\User');
        }
        return $this->userRepository;
    }

    /**
     * checks if the API key and the consumer API url is set
     */
    protected function isApiConfigured() {
        return ($this->apiKey != false && $this->consumerApiUrl != false);
    }

    /**
     * sends the notification to the consumer system
     *
     * @param string $action
     * @param string $type
     * @param int $id
     */
    public function notify($action, $type, $id)
    {
        if ($this->isApiConfigured()) {
            $command = new Command($action, $type, $id);


            /**
             * @todo: Keine Benachrichtigung bei Bestandsänderung
             */

            $this->sendNotification($command);
        }
    }

    /**
     * @param Command $command
     *
     * @return mixed
     */
    abstract protected function sendNotification(Command $command);
}