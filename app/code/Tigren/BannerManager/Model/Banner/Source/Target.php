<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\Banner\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Target
 *
 * @package Tigren\BannerManager\Model\Banner\Source
 */
class Target implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => '--Select type--', 'value' => 0],
            ['label' => __('All Images'), 'value' => 1],
            ['label' => __('Random'), 'value' => 2],
            ['label' => __('Slider'), 'value' => 3],
            ['label' => __('Slider with description'), 'value' => 4],
            ['label' => __('Fade'), 'value' => 5],
            ['label' => __('Fade with description'), 'value' => 6]
        ];
    }
}
