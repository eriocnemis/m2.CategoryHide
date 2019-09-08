<?php
/**
 * Copyright © Eriocnemis, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Eriocnemis\CategoryHide\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Catalog\Model\Indexer\Category\Flat\State as FlatState;
use \Eriocnemis\CategoryHide\Helper\Data as Helper;

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
     * Helper
     *
     * @var Helper
     */
    protected $helper;

    /**
     * Flat state
     *
     * @var FlatState
     */
    protected $flatState;

    /**
     * Initialize observer
     *
     * @param Helper $helper
     * @param FlatState $flatState
     */
    public function __construct(
        Helper $helper,
        FlatState $flatState
    ) {
        $this->helper = $helper;
        $this->flatState = $flatState;
    }

    /**
     * Filtering categories
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $collection = $observer->getEvent()->getCategoryCollection();
        if ($this->helper->isEnabled() && !$collection->hasFlag(self::JOIN_FLAG)) {
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
     * @param $collection
     * @param string $alias
     * @return \Zend_Db_Expr
     */
    protected function getCategoryExpression($collection)
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
     * @param $collection
     * @return \Zend_Db_Expr
     */
    protected function getProductExpression($collection)
    {
        return new \Zend_Db_Expr(
            (string)$collection->getConnection()->select()->from(
                ['p' => $collection->getTable('catalog_category_product')],
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
        $alias = $this->flatState->isAvailable() ? 'main_table' : 'e';
        return new \Zend_Db_Expr(
            join(
                ' OR ',
                [
                    'c.path LIKE CONCAT(' . $alias . '.path, "/%")',
                    'c.entity_id = ' . $alias . '.entity_id'
                ]
            )
        );
    }
}
