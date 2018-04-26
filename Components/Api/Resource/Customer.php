<?php
namespace Port1Typo3Connector\Components\Api\Resource;

/**
 * Class Customer
 *
 * @package Port1Typo3Connector\Components\Api\Resource
 */
class Customer extends \Shopware\Components\Api\Resource\Customer
{

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getListQuery()
    {
        return $this->getRepository()
                    ->createQueryBuilder('customer')
                    ->addSelect('attribute')
                    ->leftJoin('customer.attribute', 'attribute');
    }
}
