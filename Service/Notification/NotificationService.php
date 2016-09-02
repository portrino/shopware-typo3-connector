<?php

namespace Port1Typo3Connector\Service\Notification;

    /**
     * Copyright (C) portrino GmbH - All Rights Reserved
     * Unauthorized copying of this file, via any medium is strictly prohibited
     * Proprietary and confidential
     * Written by André Wuttig <wuttig@portrino.de>, portrino GmbH
     */

/**
 * Class NotificationService
 *
 * @package Port1Typo3Connector\Service\Notification
 */
class NotificationService
{
    /**
     * @var \Shopware\Models\User\Repository
     */
    protected $userRepository = null;

    /**
     * @var string
     */
    protected $typo3ApiUrl = '';

    /**
     * NotificationService constructor.
     */
    public function __construct()
    {
        $id = Shopware()->Container()->get('auth')->getIdentity()->id;

        /** @var array|null $data */
        $data = $this->getUserRepository()
                     ->getAttributesQuery($id)
                     ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $this->typo3ApiUrl = trim($data['typo3ApiUrl']);
    }


    /**
     * Helper function to get access to the user repository.
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
     * sends the notification to TYPO3
     *
     * @param $action
     * @param $type
     * @param $id
     */
    public function notify($action, $type, $id)
    {

    }

}