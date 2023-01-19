<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Address;

use Magento\Customer\Api\Data\AddressExtensionInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * Class ExtensionAttributes
 *
 * @package Tigren\CustomAddress\Model\Address
 */
class AdditionalAttributes extends AbstractSimpleObject implements AddressExtensionInterface
{
    /**
     * @param string $cityId
     * @return void
     */
    public function setCityId($cityId)
    {
        $this->setData('city_id', $cityId);
    }

    /**
     * @return mixed|null
     */
    public function getCityId()
    {
        return $this->_get('city_id');
    }

    /**
     * @param string $subdistrictId
     * @return void
     */
    public function setSubdistrictId($subdistrictId)
    {
        $this->setData('subdistrict_id', $subdistrictId);
    }

    /**
     * @return mixed|null
     */
    public function getSubdistrictId()
    {
        return $this->_get('subdistrict_id');
    }

    /**
     * @param string $subdistrict
     * @return void
     */
    public function setSubdistrict($subdistrict)
    {
        $this->setData('subdistrict', $subdistrict);
    }

    /**
     * @return mixed|null
     */
    public function getSubdistrict()
    {
        return $this->_get('subdistrict');
    }
}
