<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Magento\Framework\Api\ExtensionAttributesInterface;
use Tigren\CustomAddress\Api\Data\CityExtensionInterface;
use Tigren\CustomAddress\Api\Data\CityInterface;

/**
 * Class City
 * @package Tigren\CustomAddress\Model\Data
 */
class City extends AbstractExtensibleObject implements
    CityInterface
{
    /**
     * Get city code
     *
     * @return string
     */
    public function getCityCode()
    {
        return $this->_get(self::CITY_CODE);
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->_get(self::CITY);
    }

    /**
     * Get city id
     *
     * @return int
     */
    public function getCityId()
    {
        return $this->_get(self::CITY_ID);
    }

    /**
     * Set city code
     *
     * @param string $cityCode
     * @return $this
     */
    public function setCityCode($cityCode)
    {
        return $this->setData(self::CITY_CODE, $cityCode);
    }

    /**
     * Set city
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * Set city id
     *
     * @param int $cityId
     * @return $this
     */
    public function setCityId($cityId)
    {
        return $this->setData(self::CITY_ID, $cityId);
    }

    /**
     * {@inheritdoc}
     *
     * @return ExtensionAttributesInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     *
     * @param CityExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        CityExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
