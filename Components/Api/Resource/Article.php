<?php
namespace Port1Typo3Connector\Components\Api\Resource;

use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Shop\Shop;

/**
 * Class Article
 *
 * @package Port1Typo3Connector\Components\Api\Resource
 */
class Article extends \Shopware\Components\Api\Resource\Article
{

    /**
     * @param int $offset
     * @param int $limit
     * @param array $criteria
     * @param array $orderBy
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function getList($offset = 0, $limit = 25, array $criteria = [], array $orderBy = [], array $options = [])
    {
        $this->checkPrivilege('read');

        /** @var QueryBuilder $builder */
        $builder = $this->getRepository()->createQueryBuilder('article')
                        ->addSelect([
                            'attribute',
                            'categories'
                        ])
                        ->addSelect('mainDetail.lastStock')
                        ->leftJoin('article.mainDetail', 'mainDetail')
                        ->leftJoin('mainDetail.attribute', 'attribute')
                        ->leftJoin('article.categories', 'categories');

        $builder->addFilter($criteria)
                ->addOrderBy($orderBy)
                ->setFirstResult($offset)
                ->setMaxResults($limit);

        $query = $builder->getQuery();

        $query->setHydrationMode($this->getResultMode());

        $paginator = $this->getManager()->createPaginator($query);

        // Returns the total count of the query
        $totalResult = $paginator->count();

        /**
         * @Deprecated
         *
         * To support Shopware <= 5.3 we make sure the lastStock-column of the main variant is being used instead of the
         * one on the product itself.
         */
        $articles = array_map(function (array $val) {
            $val[0]['lastStock'] = $val['lastStock'];
            unset($val['lastStock']);

            return $val[0];
        }, $paginator->getIterator()->getArrayCopy());

        if ($this->getResultMode() === self::HYDRATE_ARRAY
            && isset($options['language'])
            && !empty($options['language'])) {
            /** @var $shop Shop */
            $shop = $this->findEntityByConditions(Shop::class, [
                ['id' => $options['language']],
            ]);

            foreach ($articles as &$article) {
                $article = $this->translateArticle(
                    $article,
                    $shop
                );
            }
        }

        return ['data' => $articles, 'total' => $totalResult];
    }
}
