<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class CalendarViewMode
 *
 * @package Tigren\Events\Model\Config\Source
 */
class CalendarViewMode implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'agendaDay', 'label' => __('Day')],
            ['value' => 'agendaWeek', 'label' => __('Week')],
            ['value' => 'month', 'label' => __('Month')]
        ];
    }
}
