<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Customer\Model\Data;

/**
 * Class Address
 *
 *
 * @api
 * @since 100.0.2
 */
class Address extends \Magento\Customer\Model\Data\Address
{
    /**
     *
     */
    const CITY_ID = 'city_id';
    /**
     *
     */
    const SUBDISTRICT = 'subdistrict';
    /**
     *
     */
    const SUBDISTRICT_ID = 'subdistrict_id';

    /**
     * Get city ID
     *
     * @return int
     */
    public function getCityId()
    {
        return $this->_get(self::CITY_ID);
    }

    /**
     * Set city ID
     *
     * @param int $cityId
     * @return $this
     */
    public function setCityId($cityId)
    {
        return $this->setData(self::CITY_ID, $cityId);
    }

    /**
     * Get subdistrict
     *
     * @return mixed
     */
    public function getSubdistrict()
    {
        return $this->_get(self::SUBDISTRICT);
    }

    /**
     * Set subdistrict
     *
     * @param string $subdistrict
     * @return $this
     */
    public function setSubdistrict($subdistrict)
    {
        return $this->setData(self::SUBDISTRICT, $subdistrict);
    }

    /**
     * Get subdistrict ID
     *
     * @return int
     */
    public function getSubdistrictId()
    {
        return $this->_get(self::SUBDISTRICT_ID);
    }

    /**
     * Set subdistrict ID
     *
     * @param int $subdistrictId
     * @return $this
     */
    public function setSubdistrictId($subdistrictId)
    {
        return $this->setData(self::SUBDISTRICT_ID, $subdistrictId);
    }
}
