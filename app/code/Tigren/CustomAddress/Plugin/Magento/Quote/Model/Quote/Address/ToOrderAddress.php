<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Quote\Model\Quote\Address;

use Closure;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Api\Data\OrderAddressInterface;

/**
 * Class ToOrderAddress
 * @package Tigren\CustomAddress\Plugin\Magento\Quote\Model\Quote\Address
 */
class ToOrderAddress
{
    /**
     * @param Address\ToOrderAddress $subject
     * @param Closure $proceed
     * @param Address $object
     * @param array $data
     * @return OrderAddressInterface
     */
    public function aroundConvert(
        Address\ToOrderAddress $subject,
        Closure $proceed,
        Address $object,
        $data = []
    ) {
        $orderAddress = $proceed($object, $data);

        $orderAddress->setCityId($object->getCityId());
        $orderAddress->setSubdistrict($object->getSubdistrict());
        $orderAddress->setSubdistrictId($object->getSubdistrictId());

        if ($object->getIsFullInvoice()) {
            $orderAddress->setIsFullInvoice($object->getIsFullInvoice());
            $orderAddress->setTaxIdentificationNumber($object->getTaxIdentificationNumber());
            $orderAddress->setHeadOffice($object->getHeadOffice());
            $orderAddress->setBranchOffice($object->getBranchOffice());
            $orderAddress->setCompany($object->getCompany());
            $orderAddress->setPersonalFirstname($object->getPersonalFirstname());
            $orderAddress->setPersonalLastname($object->getPersonalLastname());
            $orderAddress->setInvoiceType($object->getInvoiceType());
        }

        return $orderAddress;
    }
}
