<?php

namespace Port1Typo3Connector\Components\ApiUrlDecorator;

use Port1Typo3Connector\Components\ApiUrlDecorator;

/**
 * Class ApiCategoriesUrlDecorator
 *
 * @package Portrino\Typo3Connector\Components\ApiUrlDecorator
 */
class ApiCategoriesUrlDecorator extends ApiUrlDecorator
{

    /**
     * @param int $itemId
     */
    protected function getItemUrl($itemId)
{
    $arr = [
        'sViewport' => 'cat',
        'sCategory' => $itemId,
        'module' => 'frontend',
    ];
    return $this->controller->Front()->Router()->assemble($arr);
}

}