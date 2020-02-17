<?php
namespace Port1Typo3Connector\Components\ApiUrlDecorator;

use Port1Typo3Connector\Components\ApiUrlDecorator;
use Shopware\Components\Routing\Router;

/**
 * Class ApiArticlesUrlDecorator
 *
 * @package Portrino\Typo3Connector\Components\ApiUrlDecorator
 */
class ApiVariantsUrlDecorator extends ApiUrlDecorator
{

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function addUrl()
    {
        if ($this->isPxShopwareRequest) {
            try {
                $dataBefore = $this->controller->View()->getAssign('data');

                $data = $this->controller->View()->getAssign('data');
                $action = $this->controller->Request()->getActionName();

                if ($action === 'get') {
                    if (is_array($data) && isset($data['articleId'])) {
                        $url = $this->getItemUrl($data['articleId'], $data['number']);
                        $data = array_merge_recursive(
                            $this->controller->View()->getAssign('data'),
                            ['pxShopwareUrl' => $url]
                        );
                    }
                }

                if ($action === 'index') {
                    if (is_array($data)) {
                        // add article urls to each article of the list
                        $items = $this->controller->View()->getAssign('data');
                        foreach ($items as $key => $item) {
                            $item['pxShopwareUrl'] = $this->getItemUrl($item['articleId'], $item['number']);
                            $items[$key] = $item;
                        }
                        $data = $items;
                    }

                }

                $this->controller->View()->clearAssign('data');
                $this->controller->View()->assign('data', $data);

                /**
                 * we have to call postDispatch again to force Zend_Json::encode call
                 */
                $this->controller->postDispatch();

            } catch (\Exception $exception) {
                /**
                 * in case of an error we should reset data and render view again with data before
                 */
                $this->controller->View()->clearAssign('data');
                $this->controller->View()->assign('data', $dataBefore);
                $this->controller->postDispatch();
            }
        }
    }

    /**
     * @param int $itemId
     * @param string $orderNumber
     * @return null|string
     * @throws \Exception
     */
    protected function getItemUrl($itemId, $orderNumber = null)
    {
        $arr = [
            'sViewport' => 'detail',
            'sArticle' => $itemId,
            'module' => 'frontend',
            'forceSecure' => true,
            'number' => $orderNumber,
        ];
        $result = null;
        $router = $this->controller->Front()->Router();
        if ($router instanceof Router) {
            $url = $router->assemble($arr);
            if ($url !== false) {
                if ($router->getContext()->isUrlToLower()) {
                    if (strpos($url, '?') !== false) {
                        list($uri, $params) = explode('?', $url);
                        $url = strtolower($uri) . '?' . $params;
                    } else {
                        $url = strtolower($url);
                    }
                }
                $result = $url;
            }
        }
        return $result;
    }

}