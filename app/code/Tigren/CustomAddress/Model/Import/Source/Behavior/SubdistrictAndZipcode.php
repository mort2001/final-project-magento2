<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Import\Source\Behavior;

use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Source\Import\AbstractBehavior;

/**
 * Class Custom
 * @package Tigren\CustomAddress\Model\Import\Source\Behavior
 */
class SubdistrictAndZipcode extends AbstractBehavior
{
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add/Update'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'custom_subdistrict_and_zipcode';
    }
}
