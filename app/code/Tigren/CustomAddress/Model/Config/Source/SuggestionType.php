<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SuggestionType
 * @package Tigren\CustomAddress\Model\Config\Source
 */
class SuggestionType implements ArrayInterface
{
    /**
     *
     */
    const SUGGESTION_TYPE_DROP_DOWN = 1;

    /**
     *
     */
    const SUGGESTION_TYPE_AUTO_COMPLETE = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SUGGESTION_TYPE_DROP_DOWN,
                'label' => 'Drop-down'
            ],
            [
                'value' => self::SUGGESTION_TYPE_AUTO_COMPLETE,
                'label' => 'Autocomplete'
            ]
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::SUGGESTION_TYPE_DROP_DOWN => 'Drop-down',
            self::SUGGESTION_TYPE_AUTO_COMPLETE => 'Autocomplete'
        ];
    }
}
