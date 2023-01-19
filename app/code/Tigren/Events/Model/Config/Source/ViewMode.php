<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ViewMode
 *
 * @package Tigren\Events\Model\Config\Source
 */
class ViewMode implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'grid', 'label' => __('Grid')],
            ['value' => 'calendar', 'label' => __('Calendar')],
            ['value' => 'grid-calendar', 'label' => __('Grid(default)/Calendar')],
            ['value' => 'calendar-grid', 'label' => __('Calendar(default)/Grid')]
        ];
    }
}
