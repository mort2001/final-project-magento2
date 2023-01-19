<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface CityInterface
 * @package Tigren\CustomAddress\Api\Data
 */
interface CityInterface extends ExtensibleDataInterface
{
    /**
     *
     */
    const CITY_CODE = 'city_code';
    /**
     *
     */
    const CITY = 'city';
    /**
     *
     */
    const CITY_ID = 'city_id';
    /**#@-*/

    /**
     * Get city code
     *
     * @return string
     */
    public function getCityCode();

    /**
     * Set city code
     *
     * @param string $cityCode
     * @return $this
     */
    public function setCityCode($cityCode);

    /**
     * Get city
     *
     * @return string
     */
    public function getCity();

    /**
     * Set city
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city);

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
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Tigren\CustomAddress\Api\Data\CityExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Tigren\CustomAddress\Api\Data\CityExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Tigren\CustomAddress\Api\Data\CityExtensionInterface $extensionAttributes
    );
}
