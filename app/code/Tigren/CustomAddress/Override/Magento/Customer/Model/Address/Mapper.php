<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Customer\Model\Address;

use Magento\Customer\Api\Data\AddressInterface;

/**
 * Class Mapper converts Address Service Data Object to an array
 */
class Mapper extends \Magento\Customer\Model\Address\Mapper
{
    /**
     * Convert address data object to a flat array
     *
     * @param AddressInterface $addressDataObject
     * @return array
     */
    public function toFlatArray($addressDataObject)
    {
        $flatAddressArray = parent::toFlatArray($addressDataObject);

        $flatAddressArray['region'] = $addressDataObject->getRegion()->getRegion();
        $flatAddressArray['city'] = $addressDataObject->getCity();
        $flatAddressArray['city_id'] = $addressDataObject->getCityId();
        $flatAddressArray['subdistrict'] = $addressDataObject->getSubdistrict();
        $flatAddressArray['subdistrict_id'] = $addressDataObject->getSubdistrictId();

        return $flatAddressArray;
    }
}
