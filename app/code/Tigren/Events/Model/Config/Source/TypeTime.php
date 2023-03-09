<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2023 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ViewMode
 *
 * @package Tigren\Events\Model\Config\Source
 */
class TypeTime implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('12h')],
            ['value' => '1', 'label' => __('24h')]
        ];
    }
}
