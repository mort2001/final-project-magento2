<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Sales\Block\Adminhtml\Order\Create\Form;

use Magento\Framework\Exception\LocalizedException;
use Tigren\CustomAddress\Block\Adminhtml\Customer\Edit\Renderer\City;
use Tigren\CustomAddress\Block\Adminhtml\Customer\Edit\Renderer\CountryId;
use Tigren\CustomAddress\Block\Adminhtml\Customer\Edit\Renderer\Region;
use Tigren\CustomAddress\Block\Adminhtml\Customer\Edit\Renderer\Subdistrict;

/**
 * Class Account
 * @package Tigren\CustomAddress\Override\Magento\Sales\Block\Adminhtml\Order\Create\Form
 */
class Account extends \Magento\Sales\Block\Adminhtml\Order\Create\Form\Account
{
    /**
     * Return array of additional form element renderers by element id
     *
     * @return array
     * @throws LocalizedException
     */
    protected function _getAdditionalFormElementRenderers()
    {
        return [
            'country_id' => $this->getLayout()->createBlock(
                CountryId::class
            ),
            'region' => $this->getLayout()->createBlock(
                Region::class
            ),
            'city' => $this->getLayout()->createBlock(
                City::class
            ),
            'subdistrict' => $this->getLayout()->createBlock(
                Subdistrict::class
            )
        ];
    }
}
