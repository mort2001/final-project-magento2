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
 * Class City
 * @package Tigren\CustomAddress\Model
 */
class City extends AbstractModel
{
    /**
     * Retrieve city name
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
     * Load city by code
     *
     * @param string $code
     * @param string $regionId
     * @return $this
     * @throws LocalizedException
     */
    public function loadByCode($code, $regionId)
    {
        if ($code) {
            $this->_getResource()->loadByCode($this, $code, $regionId);
        }
        return $this;
    }

    /**
     * Load city by name
     *
     * @param string $name
     * @param string $regionId
     * @return $this
     * @throws LocalizedException
     */
    public function loadByName($name, $regionId)
    {
        $this->_getResource()->loadByName($this, $name, $regionId);
        return $this;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\City::class);
    }
}
