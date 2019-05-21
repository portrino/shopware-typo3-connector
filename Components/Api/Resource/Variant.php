<?php
namespace Port1Typo3Connector\Components\Api\Resource;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Axel Boeswetter <boeswetter@portrino.de>, portrino GmbH
 */

use Shopware\Bundle\StoreFrontBundle\Struct\Media;
use Shopware\Bundle\StoreFrontBundle\Struct\Product;
use Shopware\Components\Api\Exception as ApiException;
use Shopware\Components\Api\Resource\Translation;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Shop\Shop;

/**
 * Class Article
 *
 * @package Port1Typo3Connector\Components\Api\Resource
 */
class Variant extends \Shopware\Components\Api\Resource\Variant
{

    /**
     * @param int $offset
     * @param int $limit
     * @param array $criteria
     * @param array $orderBy
     * @param array $options
     *
     * @return array
     * @throws ApiException\CustomValidationException
     * @throws ApiException\PrivilegeException
     * @throws \Exception
     */
    public function getList($offset = 0, $limit = 25, array $criteria = [], array $orderBy = [], array $options = [])
    {
        $this->checkPrivilege('read');

        /** @var QueryBuilder $builder */
        $builder = $this->getRepository()->createQueryBuilder('detail')
                        ->addSelect([
                            'prices',
                            'attribute',
                            'partial article.{id,name,description,descriptionLong,active,taxId,changed}',
                            'customerGroup',
                            'images'
                        ])
                        ->leftJoin('detail.prices', 'prices')
                        ->innerJoin('prices.customerGroup', 'customerGroup')
                        ->leftJoin('detail.attribute', 'attribute')
                        ->innerJoin('detail.article', 'article')
                        ->leftJoin('article.images', 'images')
                        ->addFilter($criteria)
                        ->addOrderBy($orderBy)
                        ->setFirstResult($offset)
                        ->setMaxResults($limit);

        $query = $builder->getQuery();

        $query->setHydrationMode($this->getResultMode());

        $paginator = $this->getManager()->createPaginator($query);

        // Returns the total count of the query
        $totalResult = $paginator->count();

        // Returns the product data
        $variants = $paginator->getIterator()->getArrayCopy();

        if (($this->getResultMode() === self::HYDRATE_ARRAY)
            && isset($options['considerTaxInput'])
            && $options['considerTaxInput']
        ) {
            foreach ($variants as &$variant) {
                $variant = $this->considerTaxInput($variant);
            }
            unset($variant);
        }

        try {
            $frontController = Shopware()->Front();
            if ($frontController) {
                $params = $frontController->Request()->getParams();
                if (!array_key_exists('language', $options) && array_key_exists('language', $params)) {
                    $options['language'] = $params['language'];
                }
            }
        } catch (\Exception $e) {
            // ...
        }

        if ($this->getResultMode() === self::HYDRATE_ARRAY
            && isset($options['language'])
            && !empty($options['language'])) {
            /** @var Shop $shop */
            $shop = $this->findEntityByConditions(Shop::class, [
                ['id' => $options['language']],
            ]);

            /** @var array $variant */
            foreach ($variants as &$variant) {
                $variant['article'] = $this->translateArticle($variant['article'], $shop);

                /** @var \Shopware\Bundle\StoreFrontBundle\Service\ProductServiceInterface $contextService */
                $productService = $this->container->get('shopware_storefront.product_service');
                /** @var \Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface $contextService */
                $contextService = $this->container->get('shopware_storefront.context_service');
                /** @var Product $product */
                $product = $productService->get($variant['number'], $contextService->createShopContext($shop->getId()));

                $variant['article']['images'] = $this->getSortedArticleImages($variant['article']['images'], $product);
            }
            unset($variant);
            $this->translateVariants($variants, $shop);
        }

        return ['data' => $variants, 'total' => $totalResult];
    }

    /**
     * @param array $variant
     *
     * @return array
     * @throws ApiException\CustomValidationException
     *
     */
    private function considerTaxInput(array $variant)
    {
        $tax = Shopware()->Db()->fetchOne(
            'SELECT tax
                 FROM s_core_tax
                     INNER JOIN s_articles
                         ON s_articles.taxID = s_core_tax.id
                         AND s_articles.id = :articleId',
            [':articleId' => $variant['articleId']]
        );

        if (empty($tax)) {
            throw new ApiException\CustomValidationException(
                sprintf('No product tax configured for variant: %s', $variant['id'])
            );
        }

        $variant['prices'] = $this->getArticleResource()->getTaxPrices(
            $variant['prices'],
            $tax
        );

        return $variant;
    }

    /**
     * @return Translation
     */
    protected function getTranslationResource()
    {
        /** @var Translation $return */
        $return = $this->getResource('Translation');

        return $return;
    }

    /**
     * Translate the whole product array.
     *
     * @param array $data
     * @param Shop $shop
     *
     * @return array
     */
    protected function translateArticle(array $data, Shop $shop)
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

    /**
     * Translates the passed values array with the passed shop entity.
     *
     * @param array $values
     * @param Shop $shop
     *
     * @return mixed
     */
    protected function translatePropertyValues($values, Shop $shop)
    {
        if (empty($values)) {
            return $values;
        }

        foreach ($values as &$value) {
            $translation = $this->getSingleTranslation(
                'propertyvalue',
                $shop->getId(),
                $value['id']
            );
            if (empty($translation)) {
                continue;
            }

            $translation['data']['value'] = $translation['data']['optionValue'];

            $value = $this->mergeTranslation(
                $value,
                $translation['data']
            );
        }

        return $values;
    }

    /**
     * Translates the passed supplier data.
     *
     * @param array $supplier
     * @param Shop $shop
     *
     * @return array
     */
    protected function translateSupplier($supplier, Shop $shop)
    {
        if (empty($supplier)) {
            return $supplier;
        }
        $translation = $this->getSingleTranslation(
            'supplier',
            $shop->getId(),
            $supplier['id']
        );

        if (empty($translation)) {
            return $supplier;
        }

        return $this->mergeTranslation(
            $supplier,
            $translation['data']
        );
    }

    /**
     * Translates the passed property group data.
     *
     * @param array $groupData
     * @param Shop $shop
     *
     * @return array
     */
    protected function translatePropertyGroup($groupData, Shop $shop)
    {
        if (empty($groupData)) {
            return $groupData;
        }

        $translation = $this->getSingleTranslation(
            'propertygroup',
            $shop->getId(),
            $groupData['id']
        );

        if (empty($translation)) {
            return $groupData;
        }

        $translation['data']['name'] = $translation['data']['groupName'];

        return $this->mergeTranslation(
            $groupData,
            $translation['data']
        );
    }

    /**
     * Translates the passed variants array and all associated data.
     *
     * @param array $details
     * @param Shop $shop
     *
     * @return mixed
     */
    protected function translateVariants($details, Shop $shop)
    {
        if (empty($details)) {
            return $details;
        }

        foreach ($details as &$variant) {
            $translation = $this->getSingleTranslation(
                'variant',
                $shop->getId(),
                $variant['id']
            );
            if (empty($translation)) {
                continue;
            }
            $variant = $this->mergeTranslation(
                $variant,
                $translation['data']
            );
            $variant['attribute'] = $this->mergeTranslation(
                $variant['attribute'],
                $translation['data']
            );

            if ($variant['configuratorOptions']) {
                $variant['configuratorOptions'] = $this->translateAssociation(
                    $variant['configuratorOptions'],
                    $shop,
                    'configuratoroption'
                );
            }

            if ($variant['images']) {
                foreach ($variant['images'] as &$image) {
                    $translation = $this->getSingleTranslation(
                        'articleimage',
                        $shop->getId(),
                        $image['parentId']
                    );
                    if (empty($translation)) {
                        continue;
                    }
                    $image = $this->mergeTranslation($image, $translation['data']);
                }
            }
        }

        return $details;
    }

    /**
     * Helper function which merges the translated data into the already
     * existing data object. This function merges only values, which already
     * exist in the original data array.
     *
     * @param array $data
     * @param array $translation
     *
     * @return array
     */
    protected function mergeTranslation($data, $translation)
    {
        $data = array_merge(
            $data,
            array_intersect_key($translation, $data)
        );

        return $data;
    }

    /**
     * Helper function which translates associated array data.
     *
     * @param array $association
     * @param Shop $shop
     * @param string $type
     *
     * @return array
     */
    protected function translateAssociation(array $association, Shop $shop, $type)
    {
        foreach ($association as &$item) {
            $translation = $this->getSingleTranslation(
                $type,
                $shop->getId(),
                $item['id']
            );
            if (empty($translation)) {
                continue;
            }
            $item = $this->mergeTranslation($item, $translation['data']);
        }

        return $association;
    }

    /**
     * Helper function to get a single translation.
     *
     * @param string $type
     * @param int $shopId
     * @param string $key
     *
     * @return array
     */
    protected function getSingleTranslation($type, $shopId, $key)
    {
        $translation = $this->getTranslationResource()->getList(0, 1, [
            ['property' => 'translation.type', 'value' => $type],
            ['property' => 'translation.key', 'value' => $key],
            ['property' => 'translation.shopId', 'value' => $shopId],
        ]);

        return $translation['data'][0];
    }

    /**
     * @param array $images
     * @param Product $product
     * @return array
     */
    private function getSortedArticleImages($images, $product)
    {
        $result = [];

        if ($product->getCover()) {
            foreach ($images as $image) {
                if ($image['mediaId'] === $product->getCover()->getId()) {
                    $result[] = $image;
                }
            }
        }
        if ($product->getMedia()) {
            /** @var Media $media */
            foreach ($product->getMedia() as $media) {
                foreach ($images as $image) {
                    if ($image['mediaId'] === $media->getId()) {
                        if (!$product->getCover() || ($product->getCover() && $media->getId() !== $product->getCover()->getId())) {
                            $result[] = $image;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
