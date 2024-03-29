<?php
namespace Port1Typo3Connector\Components\ApiUrlDecorator;

use Port1Typo3Connector\Components\ApiUrlDecorator;
use Shopware\Components\Routing\Router;

/**
 * Class ApiCategoriesUrlDecorator
 *
 * @package Portrino\Typo3Connector\Components\ApiUrlDecorator
 */
class ApiCategoriesUrlDecorator extends ApiUrlDecorator
{

    /**
     * @param int $itemId
     * @param string $orderNumber
     * @return null|string
     * @throws \Exception
     */
    protected function getItemUrl($itemId, $orderNumber = null)
    {
        $arr = [
            'sViewport' => 'cat',
            'sCategory' => $itemId,
            'module' => 'frontend',
        ];
        $result = null;
        $router = $this->controller->Front()->Router();
        if ($router instanceof Router) {
            $url = $router->assemble($arr);
            if ($url !== false) {
                $result = $router->getContext()->isUrlToLower() ?
                    strtolower($url) :
                    $url;
            }
        }
        return $result;
    }
}
