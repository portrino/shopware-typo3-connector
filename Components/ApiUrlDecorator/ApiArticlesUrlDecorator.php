<?php

namespace Port1Typo3Connector\Components\ApiUrlDecorator;


use Port1Typo3Connector\Components\ApiUrlDecorator;
use Shopware\Components\Routing\Context;

/**
 * Class ApiArticlesUrlDecorator
 *
 * @package Portrino\Typo3Connector\Components\ApiUrlDecorator
 */
class ApiArticlesUrlDecorator extends ApiUrlDecorator
{

    /**
     * @param int $itemId
     */
    protected function getItemUrl($itemId)
    {
        $arr = [
            'sViewport' => 'detail',
            'sArticle' => $itemId,
            'module' => 'frontend',
            'forceSecure' => true
        ];

        return $this->controller->Front()->Router()->assemble($arr);
    }

}