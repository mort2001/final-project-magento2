<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Magento\Framework\Api\ExtensionAttributesInterface;
use Tigren\CustomAddress\Api\Data\SubdistrictExtensionInterface;
use Tigren\CustomAddress\Api\Data\SubdistrictInterface;

/**
 * Class Subdistrict
 * @package Tigren\CustomAddress\Model\Data
 */
class Subdistrict extends AbstractExtensibleObject implements
    SubdistrictInterface
{
    /**
     * Get subdistrict code
     *
     * @return string
     */
    public function getSubdistrictCode()
    {
        return $this->_get(self::SUBDISTRICT_CODE);
    }

    /**
     * Get subdistrict
     *
     * @return string
     */
    public function getSubdistrict()
    {
        return $this->_get(self::SUBDISTRICT);
    }

    /**
     * Get subdistrict id
     *
     * @return int
     */
    public function getSubdistrictId()
    {
        return $this->_get(self::SUBDISTRICT_ID);
    }

    /**
     * Set subdistrict code
     *
     * @param string $subdistrictCode
     * @return $this
     */
    public function setSubdistrictCode($subdistrictCode)
    {
        return $this->setData(self::SUBDISTRICT_CODE, $subdistrictCode);
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
     * Set subdistrict id
     *
     * @param int $subdistrictId
     * @return $this
     */
    public function setSubdistrictId($subdistrictId)
    {
        return $this->setData(self::SUBDISTRICT_ID, $subdistrictId);
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
     * @param SubdistrictExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        SubdistrictExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
