<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory;

/**
 * Class City
 * @package Tigren\CustomAddress\Model\Config\Source
 */
class City implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $cityCollectionFactory;

    /**
     * @param CollectionFactory $cityCollectionFactory
     */
    public function __construct(
        CollectionFactory $cityCollectionFactory
    )
    {
        $this->cityCollectionFactory = $cityCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->cityCollectionFactory->create()->toOptionArray();
        return $options;
    }
}
