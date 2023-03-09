<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory;

/**
 * Class Subdistrict
 * @package Tigren\CustomAddress\Model\Config\Source
 */
class Subdistrict implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    private $subdistrictCollectionFactory;

    /**
     * @param CollectionFactory $subdistrictCollectionFactory
     */
    public function __construct(
        CollectionFactory $subdistrictCollectionFactory
    ) {
        $this->subdistrictCollectionFactory = $subdistrictCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->subdistrictCollectionFactory->create()->toOptionArray();
        return $options;
    }
}
