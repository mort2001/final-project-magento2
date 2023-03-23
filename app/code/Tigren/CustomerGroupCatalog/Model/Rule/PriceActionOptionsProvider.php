<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Model\Rule;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PriceActionOptionsProvider
 * @package Tigren\CustomerGroupCatalog\Model\Rule
 */
class PriceActionOptionsProvider implements OptionSourceInterface
{
    public const SHOW = 0;
    public const HIDE = 1;
    public const REPLACE = 2;
    public const REPLACE_REQUEST = 3;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SHOW, 'label' => __('No')],
            ['value' => self::HIDE, 'label' => __('Yes')],
            ['value' => self::REPLACE, 'label' => __('Replace with text')],
            ['value' => self::REPLACE_REQUEST, 'label' => __('Replace to request form')]
        ];
    }
}
