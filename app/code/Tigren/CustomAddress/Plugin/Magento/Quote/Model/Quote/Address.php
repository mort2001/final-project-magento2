<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Quote\Model\Quote;

use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;

/**
 * Class Address
 * @package Tigren\CustomAddress\Plugin\Magento\Quote\Model\Quote
 */
class Address
{
    /**
     * @var CustomAddressHelper
     */
    protected $customAddressHelper;

    /**
     * Address constructor.
     * @param CustomAddressHelper $customAddressHelper
     */
    public function __construct(
        CustomAddressHelper $customAddressHelper
    ) {
        $this->customAddressHelper = $customAddressHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExportCustomerAddress(
        \Magento\Quote\Model\Quote\Address $subject,
        callable $proceed
    ) {
        $address = $proceed();

        $cityId = $subject->getCityId();
        $subdistrictId = $subject->getSubdistrictId();
        if ($cityId && $subdistrictId) {
            $this->customAddressHelper->updateDataAddress($address, $cityId, $subdistrictId);
        }

        return $address;
    }
}
