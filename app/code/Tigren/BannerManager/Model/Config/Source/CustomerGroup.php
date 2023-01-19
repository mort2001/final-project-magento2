<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\Config\Source;


use Magento\Framework\App\ObjectManager;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class CustomerGroup
 *
 * @package Tigren\BannerManager\Model\Config\Source
 */
class CustomerGroup implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $objectManager = ObjectManager::getInstance();
        $groupOptions = $objectManager->get('\Magento\Customer\Model\ResourceModel\Group\Collection')->toOptionArray();
        return $groupOptions;
    }
}
