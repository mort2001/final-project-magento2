<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class DisplayType
 * @package Tigren\BannerManager\Model\Config\Source
 */
class DisplayType implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => '-- Select Type --', 'value' => ''],
            ['label' => __('All Images'), 'value' => 1],
            ['label' => __('Random'), 'value' => 2],
            ['label' => __('Slider'), 'value' => 3],
            ['label' => __('Slider with Description'), 'value' => 4],
            ['label' => __('Basic Slider with Custom Direction Navigation'), 'value' => 5],
            ['label' => __('Slider with Min and Max Ranges'), 'value' => 6],
            ['label' => __('Basic Carousel'), 'value' => 7],
            ['label' => __('Fade'), 'value' => 8],
            ['label' => __('Fade with Description'), 'value' => 9],
        ];
    }
}
