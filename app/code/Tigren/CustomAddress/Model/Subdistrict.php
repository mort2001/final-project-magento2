<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Subdistrict
 * @package Tigren\CustomAddress\Model
 */
class Subdistrict extends AbstractModel
{
    /**
     * Retrieve region name
     *
     * If name is not declared, then default_name is used
     *
     * @return string
     */
    public function getName()
    {
        $name = $this->getData('name');
        if ($name === null) {
            $name = $this->getData('default_name');
        }
        return $name;
    }

    /**
     * Load region by code
     *
     * @param string $code
     * @param string $countryId
     * @return $this
     * @throws LocalizedException
     */
    public function loadByCode($code, $countryId)
    {
        if ($code) {
            $this->_getResource()->loadByCode($this, $code, $countryId);
        }
        return $this;
    }

    /**
     * Load region by name
     *
     * @param string $name
     * @param string $countryId
     * @return $this
     * @throws LocalizedException
     */
    public function loadByName($name, $countryId)
    {
        $this->_getResource()->loadByName($this, $name, $countryId);
        return $this;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Subdistrict::class);
    }
}
