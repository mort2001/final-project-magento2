<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Redirect
 *
 * @package Tigren\Ajaxlogin\Model\Config\Source
 */
class Redirect implements ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Reload')],
            ['value' => 1, 'label' => __('Customer Dashboard')],
            ['value' => 2, 'label' => __('Homepage')],
            ['value' => 3, 'label' => __('Cart Page')],
            ['value' => 4, 'label' => __('Wishlist Page')]
        ];
    }
}
