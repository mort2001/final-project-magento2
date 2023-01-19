<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class CalendarLanguage
 *
 * @package Tigren\Events\Model\Config\Source
 */
class CalendarLanguage implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['value' => 'de', 'label' => __('German')];
        $options[] = ['value' => 'en', 'label' => __('English')];
        $options[] = ['value' => 'fr', 'label' => __('French')];
        $options[] = ['value' => 'it', 'label' => __('Italian')];
        $options[] = ['value' => 'ru', 'label' => __('Russian')];
        $options[] = ['value' => 'vi', 'label' => __('Vietnam')];

        return $options;
    }
}
