<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Checkout\Model;

use Magento\Customer\Model\AddressFactory;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;
use Tigren\CustomAddress\Model\CityFactory;
use Tigren\CustomAddress\Model\SubdistrictFactory;

/**
 * Class DefaultConfigProvider
 * @package Tigren\CustomAddress\Plugin\Magento\Checkout\Model
 */
class DefaultConfigProvider
{
    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @var CityFactory
     */
    protected $cityFactory;

    /**
     * @var SubdistrictFactory
     */
    protected $subdistrictFactory;

    /**
     * @var CustomAddressHelper
     */
    private $customAddressHelper;

    /**
     * DefaultConfigProvider constructor.
     * @param AddressFactory $addressFactory
     * @param CustomAddressHelper $customAddressHelper
     * @param CityFactory $cityFactory
     * @param SubdistrictFactory $subdistrictFactory
     */
    public function __construct(
        AddressFactory $addressFactory,
        CustomAddressHelper $customAddressHelper,
        CityFactory $cityFactory,
        SubdistrictFactory $subdistrictFactory
    ) {
        $this->addressFactory = $addressFactory;
        $this->customAddressHelper = $customAddressHelper;
        $this->cityFactory = $cityFactory;
        $this->subdistrictFactory = $subdistrictFactory;
    }

    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, $result)
    {
        if (!empty($result['customerData']['addresses'])) {
            foreach ($result['customerData']['addresses'] as &$address) {
                $customAttributes = [];
                $addressModel = $this->addressFactory->create()->load($address['id']);
                $fields = ['city_id', 'subdistrict', 'subdistrict_id'];
                foreach ($fields as $field) {
                    if ($addressModel->getData($field)) {
                        $customAttributes[$field] = $addressModel->getData($field);
                    }
                }

                if (!empty($customAttributes)) {
                    $address['custom_attributes'] = $customAttributes;
                }

                if (!empty($address['custom_attributes']['city_id'])) {
                    $address['city'] = $this->customAddressHelper->getCityNameById($address['custom_attributes']['city_id']);
                }

                if (!empty($address['custom_attributes']['subdistrict_id'])) {
                    $address['custom_attributes']['subdistrict'] = $this->customAddressHelper->getSubdistrictNameById($address['custom_attributes']['subdistrict_id']);
                }
            }
        }

        $result['quoteData']['suggestionType'] = $this->customAddressHelper->getSuggestionType();
        $result['quoteData']['move_billing'] = $this->customAddressHelper->getMoveBilling();
        $result['quoteData']['full_tax_invoice_enabled'] = $this->customAddressHelper->isFullTaxInvoiceEnabled();

        return $result;
    }
}
