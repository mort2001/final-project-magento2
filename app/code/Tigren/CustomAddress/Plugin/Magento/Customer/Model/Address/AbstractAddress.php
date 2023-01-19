<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Customer\Model\Address;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Address\Config;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Tigren\CustomAddress\Api\Data\CityInterfaceFactory;
use Tigren\CustomAddress\Model\CityFactory;
use Tigren\CustomAddress\Model\ResourceModel\City\Collection;
use Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory;

/**
 * Class AbstractAddress
 * @package Tigren\CustomAddress\Plugin\Magento\Customer\Model\Address
 */
class AbstractAddress extends \Magento\Customer\Model\Address\AbstractAddress
{
    /**
     * @var CityInterfaceFactory
     */
    protected $_cityDataFactory;

    /**
     * @var CityFactory
     */
    protected $_cityFactory;

    /**
     * @var CollectionFactory
     */
    protected $_cityCollectionFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $directoryData
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param Config $addressConfig
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param AddressMetadataInterface $metadataService
     * @param AddressInterfaceFactory $addressDataFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param CityInterfaceFactory
     * @param CityFactory
     * @param CollectionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $directoryData,
        \Magento\Eav\Model\Config $eavConfig,
        Config $addressConfig,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        AddressMetadataInterface $metadataService,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory,
        DataObjectHelper $dataObjectHelper,
        CityInterfaceFactory $cityDataFactory,
        CityFactory $cityFactory,
        CollectionFactory $cityCollectionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_cityDataFactory = $cityDataFactory;
        $this->_cityFactory = $cityFactory;
        $this->_cityCollectionFactory = $cityCollectionFactory;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $directoryData,
            $eavConfig,
            $addressConfig,
            $regionFactory,
            $countryFactory,
            $metadataService,
            $addressDataFactory,
            $regionDataFactory,
            $dataObjectHelper,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return array|bool
     */
    public function validate()
    {
        $errors = parent::validate();

        if (($cityArrKey = array_search(
                __('%fieldName is a required field.', ['fieldName' => 'city']),
                $errors
            )) !== false) {
            unset($errors[$cityArrKey]);
        }

        return $errors;
    }

    /**
     * @return Collection
     */
    public function getCityCollection()
    {
        $collection = $this->_cityCollectionFactory->create();
        return $collection;
    }
}
