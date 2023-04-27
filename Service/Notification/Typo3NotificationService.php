<?php

namespace Port1Typo3Connector\Service\Notification;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\User\User;

/**
 * Class NotificationService
 *
 * @package Port1Typo3Connector\Service\Notification
 */
class Typo3NotificationService extends NotificationService
{

    /**
     * @var \Zend_Http_Client
     */
    protected $client = null;

    public function __construct(ModelManager $entityManager)
    {
        parent::__construct($entityManager);
    }

    /**
     * intialize the service (http client)
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isApiConfigured()) {
            $uri = \Zend_Uri_Http::fromString($this->consumerApiUrl);
            $this->client = new \Zend_Http_Client($uri);
            $header = 'SW-TOKEN apikey="' . (string)$this->apiKey . '"';
            $this->client->setHeaders('Authorization', $header);
        }
    }


    /**
     * @return string
     */
    protected function getConsumerApiUrl() {
        $result = '';
        if ($this->user && $this->user instanceof User) {
            $attribute = $this->user->getAttribute();
            if ($attribute != null) {
                $result = $this->user->getAttribute()->getTypo3ApiUrl();
            }
        }
        return $result;
    }

    /**
     * checks if the http client is initialized
     */
    protected function isHttpClientInitialized() {
        return ($this->client != null && $this->client->getUri() != '' && $this->client->getHeader('Authorization') != '');
    }

    /**
     * @param Command $command
     *
     * @return void
     */
    protected function sendNotification(Command $command) {
        if ($this->isHttpClientInitialized()) {
            $this->client->setRawData(json_encode(
                    [
                        'data' =>
                            [
                                $command
                            ]
                    ])
            );
            $this->client->request(\Zend_Http_Client::POST);
        }
    }
}
