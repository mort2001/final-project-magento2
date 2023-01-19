<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Product\Type\Event;

use Magento\Catalog\Model\Product;

/**
 * Class Price
 *
 * @package Tigren\Events\Model\Product\Type\Event
 */
class Price extends \Magento\Catalog\Model\Product\Type\Price
{
    /**
     * @param Product $product
     * @return float|mixed
     */
    public function getPrice($product)
    {
        return $product->getData('price');
    }
}
