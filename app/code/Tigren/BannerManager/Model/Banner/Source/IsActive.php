<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\Banner\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Tigren\BannerManager\Model\Banner;

/**
 * Class IsActive
 *
 * @package Tigren\BannerManager\Model\Banner\Source
 */
class IsActive implements OptionSourceInterface
{
    /**
     * @var Banner
     */
    protected $_banner;

    /**
     * Constructor
     *
     * @param Banner $banner
     */
    public function __construct(Banner $banner)
    {
        $this->_banner = $banner;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->_banner->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
