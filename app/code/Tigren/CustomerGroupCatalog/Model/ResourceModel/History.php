<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Post
 * @package Tigren\Question\Model\ResourceModel
 */
class History extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('customergroup_catalog_history', 'entity_id');
    }
}