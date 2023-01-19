<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Api\Data;

/**
 * Interface AddressInterface
 * @api
 */
interface AddressInterface extends \Magento\Customer\Api\Data\AddressInterface
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
     * Get city id
     *
     * @return int
     */
    public function getCityId();

    /**
     * Set city id
     *
     * @param int $cityId
     * @return $this
     */
    public function setCityId($cityId);

    /**
     * Get subdistrict
     *
     * @return string
     */
    public function getSubdistrict();

    /**
     * Set subdistrict
     *
     * @param string $subdistrict
     * @return $this
     */
    public function setSubdistrict($subdistrict);

    /**
     * Get subdistrict id
     *
     * @return int
     */
    public function getSubdistrictId();

    /**
     * Set subdistrict id
     *
     * @param int $subdistrictId
     * @return $this
     */
    public function setSubdistrictId($subdistrictId);
}
