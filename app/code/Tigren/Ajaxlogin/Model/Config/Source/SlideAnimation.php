<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SlideAnimation
 *
 * @package Tigren\Ajaxlogin\Model\Config\Source
 */
class SlideAnimation implements ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'show', 'label' => __('Show')],
            ['value' => 'fade_fast', 'label' => __('Fade (Fast)')],
            ['value' => 'fade_medium', 'label' => __('Fade (Medium)')],
            ['value' => 'fade_slow', 'label' => __('Fade (Slow)')],
            ['value' => 'slide_fast', 'label' => __('Slide (Fast)')],
            ['value' => 'slide_medium', 'label' => __('Slide (Medium)')],
            ['value' => 'slide_slow', 'label' => __('Slide (Slow)')],
        ];
    }
}
