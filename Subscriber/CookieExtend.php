<?php

namespace Port1Typo3Connector\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Snippet_Manager;
use Shopware\Bundle\CookieBundle\CookieCollection;
use Shopware\Bundle\CookieBundle\Structs\CookieGroupStruct;
use Shopware\Bundle\CookieBundle\Structs\CookieStruct;

/**
 * Class CookieExtend
 *
 * @package Port1Typo3Connector\Subscriber
 */
class CookieExtend implements SubscriberInterface
{
    /**
     * @var Enlight_Components_Snippet_Manager
     */
    private $snippets;

    public function __construct(Enlight_Components_Snippet_Manager $snippets)
    {
        $this->snippets = $snippets;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'CookieCollector_Collect_Cookies' => 'addCookies'
        ];
    }

    public function addCookies(): CookieCollection
    {
        $collection = new CookieCollection();

        // TODO: get cookie names and labels from config?
        $pluginNamespace = $this->snippets->getNamespace('portrino/shopware-typo3-connector/cookie');
        $cookieNames = $pluginNamespace->get('cookieNames');
        $cookieLabels = $pluginNamespace->get('cookieLabels');

        if ($cookieNames && $cookieLabels) {
            $cookieNames = explode('|', $cookieNames);
            $cookieLabels = explode('|', $cookieLabels);

            if ($cookieNames && $cookieLabels && count($cookieNames) === count($cookieLabels)) {
                foreach ($cookieNames as $key => $name) {
                    if ($name && $cookieLabels[$key]) {
                        // TODO: different matchingPattern?
                        $collection->add(new CookieStruct(
                            $name,
                            '/^' . $name . '/',
                            $cookieLabels[$key],
                            CookieGroupStruct::TECHNICAL
                        ));
                    }
                }
            }
        }

        return $collection;
    }
}
