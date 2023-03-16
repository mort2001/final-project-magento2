<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Customer\Model;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Reflection\DataObjectProcessor;
use Tigren\CustomAddress\Api\Data\CityInterface;
use Tigren\CustomAddress\Api\Data\CityInterfaceFactory;
use Tigren\CustomAddress\Api\Data\SubdistrictInterface;
use Tigren\CustomAddress\Api\Data\SubdistrictInterfaceFactory;
use Tigren\CustomAddress\Model\CityFactory;
use Tigren\CustomAddress\Model\SubdistrictFactory;

/**
 * Class Address
 * @package Tigren\CustomAddress\Plugin\Magento\Customer\Model
 */
class Address
{
    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var CityInterfaceFactory
     */
    protected $cityDataFactory;

    /**
     * @var CityFactory
     */
    protected $cityFactory;

    /**
     * @var SubdistrictInterfaceFactory
     */
    protected $subdistrictDataFactory;

    /**
     * @var SubdistrictFactory
     */
    protected $subdistrictFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @param DataObjectProcessor $dataProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param CityInterfaceFactory $cityDataFactory
     * @param CityFactory $cityFactory
     * @param SubdistrictInterfaceFactory $subdistrictDataFactory
     * @param SubdistrictFactory $subdistrictFactory
     * @param Http $request
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DataObjectProcessor $dataProcessor,
        DataObjectHelper $dataObjectHelper,
        CityInterfaceFactory $cityDataFactory,
        CityFactory $cityFactory,
        SubdistrictInterfaceFactory $subdistrictDataFactory,
        SubdistrictFactory $subdistrictFactory,
        Http $request
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->cityDataFactory = $cityDataFactory;
        $this->cityFactory = $cityFactory;
        $this->subdistrictDataFactory = $subdistrictDataFactory;
        $this->subdistrictFactory = $subdistrictFactory;
        $this->request = $request;
    }

    /**
     * @param \Magento\Customer\Model\Address $subject
     * @param callable $proceed
     * @param AddressInterface $address
     * @return mixed
     */
    public function aroundUpdateData(
        \Magento\Customer\Model\Address $subject,
        callable $proceed,
        AddressInterface $address
    ) {
        $addressModel = $proceed($address);

        // Set all attributes
        $attributeValues = $this->dataProcessor
            ->buildOutputDataArray($address, AddressInterface::class);

        $addressData = $this->_getAddressParams($address);

        if ($address->getSubdistrictId()) {
            $this->request->setParam('subdistrict_id', $address->getSubdistrictId());
        }
        if ($address->getSubdistrict()) {
            $this->request->setParam('subdistrict', $address->getSubdistrict());
            $addressModel->setSubdistrict($address->getSubdistrict());
        }
        if ($address->getCityId()) {
            $this->request->setParam('city_id', $address->getCityId());
        }

        if (!empty($addressData['city_id'])) {
            $this->request->setParam('city_id', $addressData['city_id']);
        }
        if (!empty($addressData['subdistrict_id'])) {
            $this->request->setParam('subdistrict_id', $addressData['subdistrict_id']);
        }

        $this->updateCityData($attributeValues, $address);
        $this->updateSubdistrictData($attributeValues, $address);

        foreach ($attributeValues as $attributeCode => $attributeData) {
            if ($attributeCode === 'city' && $attributeData->getCityId()) {
                $addressModel->setCity($attributeData->getCity());
                $addressModel->setCityCode($attributeData->getCityCode());
                $addressModel->setCityId($attributeData->getCityId());
            } elseif ($attributeCode === 'subdistrict' && $attributeData->getSubdistrictId()) {
                $addressModel->setSubdistrict($attributeData->getSubdistrict());
                $addressModel->setSubdistrictCode($attributeData->getSubdistrictCode());
                $addressModel->setSubdistrictId($attributeData->getSubdistrictId());
            }
        }

        return $addressModel;
    }

    /**
     * @param $address
     * @return mixed
     */
    protected function _getAddressParams($address)
    {
        $addresses = $this->request->getParam('address');
        if (!$addresses) {
            return [];
        }

        $entityId = $address->getId();
        if ($entityId && !empty($addresses[$entityId])) {
            return $addresses[$entityId];
        }

        $index = $this->request->getParam('currentNewIndex') ?: 0;
        if (!$entityId && !empty($addresses['new_' . $index])) {
            $this->request->setParam('currentNewIndex', $index + 1);
            return $addresses['new_' . $index];
        }

        return [];
    }

    /**
     * Update city data
     *
     * @param array $attributeValues
     * @param AddressInterface $address
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function updateCityData(&$attributeValues, $address)
    {
        $customAttributes = $this->request->getParam('custom_attributes');
        $cityId = $this->request->getParam('city_id');
        $cityId = !empty($customAttributes['city_id']) ? $customAttributes['city_id'] : $cityId;
        if (!empty($cityId)) {
            $newCity = $this->cityFactory->create()->load($cityId);
            $attributeValues['city_id'] = $cityId;
            $attributeValues['city'] = $newCity->getName();
            $attributeValues['city_code'] = $newCity->getCode();
        }

        if (empty($attributeValues['city_id']) && $extensionAttributes = $address->getExtensionAttributes()) {
            $attributeValues['city_id'] = $extensionAttributes->getCityId();
            $attributeValues['city'] = $address->getCity();
            $attributeValues['city_code'] = null;
        }

        $cityData = [
            CityInterface::CITY_ID => !empty($attributeValues['city_id']) ? $attributeValues['city_id'] : null,
            CityInterface::CITY => !empty($attributeValues['city']) ? $attributeValues['city'] : null,
            CityInterface::CITY_CODE => !empty($attributeValues['city_code'])
                ? $attributeValues['city_code']
                : null,
        ];

        $city = $this->cityDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $city,
            $cityData,
            CityInterface::class
        );

        $attributeValues['city'] = $city;
    }

    /**
     * Update subdistrict data
     *
     * @param array $attributeValues
     * @param AddressInterface $address
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function updateSubdistrictData(&$attributeValues, $address)
    {
        $customAttributes = $this->request->getParam('custom_attributes');
        $subdistrictId = $this->request->getParam('subdistrict_id');
        $subdistrictId = !empty($customAttributes['subdistrict_id']) ? $customAttributes['subdistrict_id'] : $subdistrictId;
        if (!empty($subdistrictId)) {
            $newSubdistrict = $this->subdistrictFactory->create()->load($subdistrictId);
            $attributeValues['subdistrict_id'] = $subdistrictId;
            $attributeValues['subdistrict'] = $newSubdistrict->getName();
            $attributeValues['subdistrict_code'] = $newSubdistrict->getCode();
        }

        if (empty($attributeValues['subdistrict_id']) && $extensionAttributes = $address->getExtensionAttributes()) {
            $attributeValues['subdistrict_id'] = $extensionAttributes->getSubdistrictId();
            $attributeValues['subdistrict'] = $extensionAttributes->getSubdistrict();
            $attributeValues['subdistrict_code'] = null;
        }

        $subdistrictData = [
            SubdistrictInterface::SUBDISTRICT_ID => !empty($attributeValues['subdistrict_id']) ? $attributeValues['subdistrict_id'] : null,
            SubdistrictInterface::SUBDISTRICT => !empty($attributeValues['subdistrict']) ? $attributeValues['subdistrict'] : null,
            SubdistrictInterface::SUBDISTRICT_CODE => !empty($attributeValues['subdistrict_code'])
                ? $attributeValues['subdistrict_code']
                : null,
        ];

        $subdistrict = $this->subdistrictDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $subdistrict,
            $subdistrictData,
            SubdistrictInterface::class
        );

        $attributeValues['subdistrict'] = $subdistrict;
    }
}
