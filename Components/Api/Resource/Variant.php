<?php
namespace Port1Typo3Connector\Components\Api\Resource;

/**
 * Copyright (C) portrino GmbH - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Axel Boeswetter <boeswetter@portrino.de>, portrino GmbH
 */

use Shopware\Components\Api\Exception as ApiException;
use Shopware\Components\Model\QueryBuilder;

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
     * @throws ApiException\PrivilegeException
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
        }

        return ['data' => $variants, 'total' => $totalResult];
    }

    /**
     * @param array $variant
     *
     * @throws ApiException\CustomValidationException
     *
     * @return array
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
}
