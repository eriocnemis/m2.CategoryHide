<?php
/**
 * Copyright © Eriocnemis, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Eriocnemis\CategoryHide\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Catalog\Model\ResourceModel\Category\Collection;

/**
 * Load observer
 */
class LoadObserver implements ObserverInterface
{
    /**
     * Filter join flag name
     */
    const JOIN_FLAG = 'category_hide_filter';

    /**
     * Filtering categories
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $collection = $observer->getEvent()->getCategoryCollection();
        if (!$collection->hasFlag(self::JOIN_FLAG)) {
            $collection->getSelect()->where(
                'EXISTS (?)',
                $this->getCategoryExpression($collection)
            );
            $collection->setFlag(self::JOIN_FLAG, true);
        }
    }

    /**
     * Retrieve category expression
     *
     * @param Collection $collection
     * @return \Zend_Db_Expr
     */
    protected function getCategoryExpression(Collection $collection)
    {
        return new \Zend_Db_Expr(
            (string)$collection->getConnection()->select()->from(
                ['c' => $collection->getMainTable()],
                ['entity_id']
            )
            ->where('EXISTS (?)', $this->getProductExpression($collection))
            ->where('(?)', $this->getConcatExpression())
        );
    }

    /**
     * Retrieve product expression
     *
     * @param Collection $collection
     * @return \Zend_Db_Expr
     */
    protected function getProductExpression(Collection $collection)
    {
        return new \Zend_Db_Expr(
            (string)$collection->getConnection()->select()->from(
                ['p' => $collection->getProductTable()],
                ['product_id']
            )
            ->where('p.category_id = c.entity_id')
        );
    }

    /**
     * Retrieve concat expression
     *
     * @return \Zend_Db_Expr
     */
    protected function getConcatExpression()
    {
        return new \Zend_Db_Expr(
            join(
                ' OR ',
                [
                    'c.path LIKE CONCAT(e.path, "/%")',
                    'c.entity_id = e.entity_id'
                ]
            )
        );
    }
}
