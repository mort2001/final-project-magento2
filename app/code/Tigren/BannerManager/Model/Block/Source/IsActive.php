<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\Block\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Tigren\BannerManager\Model\Block;

/**
 * Class IsActive
 *
 * @package Tigren\BannerManager\Model\Block\Source
 */
class IsActive implements OptionSourceInterface
{
    /**
     * @var Block
     */
    protected $block;

    /**
     * Constructor
     *
     * @param Block $block
     */
    public function __construct(Block $block)
    {
        $this->block = $block;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->block->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
