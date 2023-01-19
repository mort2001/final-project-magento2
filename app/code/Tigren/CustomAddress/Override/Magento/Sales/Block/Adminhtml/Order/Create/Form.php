<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Sales\Block\Adminhtml\Order\Create;


use Magento\Eav\Model\AttributeDataFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Form
 * @package Tigren\CustomAddress\Override\Magento\Sales\Block\Adminhtml\Order\Create
 */
class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Form
{
    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getOrderDataJson()
    {
        $data = [];
        $this->_storeManager->setCurrentStore($this->getStoreId());

        if ($this->getCustomerId()) {
            $data['customer_id'] = $this->getCustomerId();
            $data['addresses'] = [];

            $addresses = $this->customerRepository->getById($this->getCustomerId())->getAddresses();

            foreach ($addresses as $address) {
                $addressForm = $this->_customerFormFactory->create(
                    'customer_address',
                    'adminhtml_customer_address',
                    $this->addressMapper->toFlatArray($address)
                );
                $data['addresses'][$address->getId()] = $addressForm->outputData(
                    AttributeDataFactory::OUTPUT_FORMAT_JSON
                );

                if (empty($data['addresses'][$address->getId()]['city_id'])) {
                    $data['addresses'][$address->getId()]['city_id'] = $address->getCityId();
                }

                if (empty($data['addresses'][$address->getId()]['subdistrict_id'])) {
                    $data['addresses'][$address->getId()]['subdistrict_id'] = $address->getSubdistrictId();
                }

                if (empty($data['addresses'][$address->getId()]['subdistrict'])) {
                    $data['addresses'][$address->getId()]['subdistrict'] = $address->getSubdistrict();
                }
            }
        }

        if ($this->getStoreId() !== null) {
            $data['store_id'] = $this->getStoreId();
            $currency = $this->_localeCurrency->getCurrency($this->getStore()->getCurrentCurrencyCode());
            $symbol = $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
            $data['currency_symbol'] = $symbol;
            $data['shipping_method_reseted'] = !(bool)$this->getQuote()->getShippingAddress()->getShippingMethod();
            $data['payment_method'] = $this->getQuote()->getPayment()->getMethod();
        }

        $data['quote_id'] = $this->_sessionQuote->getQuoteId();

        return $this->_jsonEncoder->encode($data);
    }
}
