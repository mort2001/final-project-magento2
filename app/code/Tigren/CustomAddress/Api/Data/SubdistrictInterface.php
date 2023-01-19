<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface SubdistrictInterface
 * @package Tigren\CustomAddress\Api\Data
 */
interface SubdistrictInterface extends ExtensibleDataInterface
{
    /**
     *
     */
    const SUBDISTRICT_CODE = 'subdistrict_code';
    /**
     *
     */
    const SUBDISTRICT = 'subdistrict';
    /**
     *
     */
    const SUBDISTRICT_ID = 'subdistrict_id';

    /**
     * Get subdistrict code
     *
     * @return string
     */
    public function getSubdistrictCode();

    /**
     * Set subdistrict code
     *
     * @param string $subdistrictCode
     * @return $this
     */
    public function setSubdistrictCode($subdistrictCode);

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

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Tigren\CustomAddress\Api\Data\SubdistrictExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Tigren\CustomAddress\Api\Data\SubdistrictExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Tigren\CustomAddress\Api\Data\SubdistrictExtensionInterface $extensionAttributes
    );
}
