<?php

namespace Port1Typo3Connector\Service\Notification;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
 */
use Shopware\Components\Model\ModelEntity;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;
use Shopware\Models\Media\Media;
use Shopware\Models\Shop\Shop;
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
     * @var ModelManager
     */
    private $entityManager;

    /**
     * CrudService constructor.
     * @param ModelManager $entityManager
     */
    public function __construct(
        ModelManager $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->initialize();
    }

    /**
     * initialize the service
     */
    protected function initialize()
    {
        $id = Shopware()->Container()->get('auth')->getIdentity()->id;
        $this->user = $this->getUserRepository()->getUserDetailQuery($id)->getOneOrNullResult();

        $this->apiKey = $this->getApiKey() != '' ? $this->getApiKey() : false;
        $this->consumerApiUrl = $this->getConsumerApiUrl() != '' ? $this->getConsumerApiUrl() : false;
    }

    /**
     * @return string
     */
    protected function getApiKey()
    {
        $result = '';
        if ($this->user && $this->user instanceof User) {
            $result = $this->user->getApiKey();
        }
        return $result;
    }

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
     *
     * @return boolean
     */
    protected function isApiConfigured()
    {
        return ($this->apiKey != false && $this->consumerApiUrl != false);
    }

    /**
     * checks if the API key and the consumer API url is set
     * @param Article|Category|Media|Shop $entity
     *
     * @return boolean
     */
    protected function isRelevantInformation($entity)
    {
        $result = true;

        if ($entity instanceof Article) {

//            @todo: check if only stock has changed and prevent notificationService from fire notification then
//            $uow = $this->entityManager->getUnitOfWork();
//            $changeset = $uow->getEntityChangeSet($entity);
//            $changedArticle = $changeset['changed'];
//            $updates = $uow->getScheduledCollectionUpdates();

        }

        return $result;
    }

    /**
     * sends the notification to the consumer system
     *
     * @param string $action
     * @param ModelEntity|Article|Category|Media|Shop $entity
     */
    public function notify($action, $entity)
    {
        if ($this->isApiConfigured()) {

            if ($this->isRelevantInformation($entity)) {

                $id = $entity->getId();
                $type = Command::getTypeFromEntity($entity);

                $command = new Command($action, $type, $id);
                $this->sendNotification($command);
            }
        }
    }

    /**
     * @return string
     */
    abstract protected function getConsumerApiUrl();

    /**
     * @param Command $command
     *
     * @return mixed
     */
    abstract protected function sendNotification(Command $command);
}