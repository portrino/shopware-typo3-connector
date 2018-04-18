<?php
namespace Port1Typo3Connector\Components\Api\Resource;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Axel Boeswetter <boeswetter@portrino.de>, portrino GmbH
 */

use Shopware\Models\Shop\Shop;

/**
 * Class Article
 *
 * @package Port1Typo3Connector\Components\Api\Resource
 */
class Article extends \Shopware\Components\Api\Resource\Article
{

    /**
     * Translate the whole article array.
     *
     * @param array $data
     * @param Shop  $shop
     *
     * @return array
     */
    protected function translateArticle(array $data, Shop $shop)
    {
        if (version_compare(\Shopware::VERSION, '5.4.0', '<')) {
            $result = $this->translateArticleDecorator($data, $shop);
        } else {
            $result = parent::translateArticle($data, $shop);
        }
        return $result;
    }

    /**
     * @param array $data
     * @param Shop $shop
     * @return array
     */
    protected function translateArticleDecorator(array $data, Shop $shop)
    {
        $this->getTranslationResource()->setResultMode(
            self::HYDRATE_ARRAY
        );
        $translation = $this->getSingleTranslation(
            'article',
            $shop->getId(),
            $data['id']
        );

        if (!empty($translation)) {
            $data = $this->mergeTranslation($data, $translation['data']);

            if ($data['mainDetail']) {
                $data['mainDetail'] = $this->mergeTranslation($data['mainDetail'], $translation['data']);

                if ($data['mainDetail']['attribute']) {
                    $data['mainDetail']['attribute'] = $this->mergeTranslation(
                        $data['mainDetail']['attribute'],
                        $translation['data']
                    );
                }

                if ($data['mainDetail']['configuratorOptions']) {
                    $data['mainDetail']['configuratorOptions'] = $this->translateAssociation(
                        $data['mainDetail']['configuratorOptions'],
                        $shop,
                        'configuratoroption'
                    );
                }
            }
        }

        $data['details'] = $this->translateVariants(
            $data['details'],
            $shop
        );

        // apply Shopware patch SW-20486 for Shopware < 5.4.0
        // -> https://github.com/shopware/shopware/commit/f5720ce37e0fedd1749e0d50dc715ae19c4191af
        // patch begin
        if (isset($data['links'])) {
            $data['links'] = $this->translateAssociation(
                $data['links'],
                $shop,
                'link'
            );
        }

        if (isset($data['downloads'])) {
            $data['downloads'] = $this->translateAssociation(
                $data['downloads'],
                $shop,
                'download'
            );
        }
        // patch end

        $data['supplier'] = $this->translateSupplier($data['supplier'], $shop);

        $data['propertyValues'] = $this->translatePropertyValues($data['propertyValues'], $shop);

        $data['propertyGroup'] = $this->translatePropertyGroup($data['propertyGroup'], $shop);

        if (!empty($data['configuratorSet']) && !empty($data['configuratorSet']['groups'])) {
            $data['configuratorSet']['groups'] = $this->translateAssociation(
                $data['configuratorSet']['groups'],
                $shop,
                'configuratorgroup'
            );
        }

        if (isset($data['related'])) {
            $data['related'] = $this->translateAssociation(
                $data['related'],
                $shop,
                'article'
            );
        }

        if (isset($data['similar'])) {
            $data['similar'] = $this->translateAssociation(
                $data['similar'],
                $shop,
                'article'
            );
        }

        if (isset($data['images'])) {
            $data['images'] = $this->translateAssociation(
                $data['images'],
                $shop,
                'articleimage'
            );
        }

        return $data;
    }
}