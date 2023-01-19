<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Block\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Downloadable\Block\Catalog\Product\Links;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Pricing\Helper\Data;

/**
 * Class Link
 *
 * @package Tigren\Ajaxcart\Block\Product
 */
class Link extends Links
{
    /**
     * Link constructor.
     *
     * @param Context          $context
     * @param Data             $pricingHelper
     * @param EncoderInterface $encoder
     * @param array            $data
     */
    public function __construct(
        Context $context,
        Data $pricingHelper,
        EncoderInterface $encoder,
        array $data = []
    ) {
        parent::__construct($context, $pricingHelper, $encoder, $data);
    }
}
