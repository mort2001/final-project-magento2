<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 *
 */

namespace Tigren\CustomAddress\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class City
 * @package Tigren\CustomAddress\Model
 */
class Region extends AbstractModel
{
    protected $_eventPrefix = 'region_model';

    protected $_eventObject = 'region_model';
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Region::class);
    }
}
