<?php
/**
 * Copyright © Eriocnemis, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Eriocnemis\CategoryHide\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Helper
 */
class Data extends AbstractHelper
{
    /**
     * hide empty categories config path
     */
    const XML_HIDE_EMPTY = 'catalog/navigation/hide_empty';

    /**
     * Check hidden empty categories functionality should be enabled
     *
     * @param string $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->isSetFlag(static::XML_HIDE_EMPTY, $storeId);
    }

    /**
     * Retrieve config flag
     *
     * @param string $path
     * @param string $storeId
     * @return bool
     */
    protected function isSetFlag($path, $storeId = null)
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
