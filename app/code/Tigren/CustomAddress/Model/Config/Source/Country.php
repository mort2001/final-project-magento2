<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Config\Source;

use Magento\Directory\Block\Data as DirectoryData;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Country
 * @package Tigren\CustomAddress\Model\Config\Source
 */
class Country implements ArrayInterface
{
    /**
     * @var DirectoryData
     */
    private $directoryData;

    /**
     * @param DirectoryData $directoryData
     */
    public function __construct(
        DirectoryData $directoryData
    ) {
        $this->directoryData = $directoryData;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->directoryData->getCountryCollection()
            ->setForegroundCountries($this->directoryData->getTopDestinations())
            ->toOptionArray();
        return $options;
    }
}
