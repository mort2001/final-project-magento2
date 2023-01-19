<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Controller\Customer;

use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Reflection\DataObjectProcessor;
use Tigren\CustomAddress\Api\Data\CityInterface;
use Tigren\CustomAddress\Api\Data\CityInterfaceFactory;
use Tigren\CustomAddress\Api\Data\SubdistrictInterface;
use Tigren\CustomAddress\Api\Data\SubdistrictInterfaceFactory;
use Tigren\CustomAddress\Model\CityFactory;
use Tigren\CustomAddress\Model\SubdistrictFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateAddress extends Action
{
    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var FormKeyValidator
     */
    protected $_formKeyValidator;

    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var FormFactory
     */
    protected $_formFactory;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var RegionInterfaceFactory
     */
    protected $regionDataFactory;

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
     * @var DataObjectProcessor
     */
    protected $_dataProcessor;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var Mapper
     */
    private $customerAddressMapper;

    /**
     * UpdateAddress constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param FormFactory $formFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param CityInterfaceFactory $cityDataFactory
     * @param CityFactory $cityFactory
     * @param SubdistrictInterfaceFactory $subdistrictDataFactory
     * @param SubdistrictFactory $subdistrictFactory
     * @param DataObjectProcessor $dataProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param ForwardFactory $resultForwardFactory
     * @param RegionFactory $regionFactory
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        FormFactory $formFactory,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory,
        CityInterfaceFactory $cityDataFactory,
        CityFactory $cityFactory,
        SubdistrictInterfaceFactory $subdistrictDataFactory,
        SubdistrictFactory $subdistrictFactory,
        DataObjectProcessor $dataProcessor,
        DataObjectHelper $dataObjectHelper,
        ForwardFactory $resultForwardFactory,
        RegionFactory $regionFactory,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->regionFactory = $regionFactory;
        $this->helperData = $helperData;
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_formFactory = $formFactory;
        $this->_addressRepository = $addressRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->regionDataFactory = $regionDataFactory;
        $this->cityDataFactory = $cityDataFactory;
        $this->cityFactory = $cityFactory;
        $this->subdistrictDataFactory = $subdistrictDataFactory;
        $this->subdistrictFactory = $subdistrictFactory;
        $this->_dataProcessor = $dataProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Process address form save
     *
     * @return Redirect
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'message' => ''
        ];

        if (!$this->getRequest()->isPost()) {
            return $this->getResponse()->representJson(
                $this->_objectManager->get(Data::class)->jsonEncode($result)
            );
        }

        try {
            $address = $this->_extractAddress();
            $this->_addressRepository->save($address);
            $result['message'] = __('You saved the address.');
            $result['success'] = true;
        } catch (Exception $e) {
            $result['message'] = __('We can\'t save the address.');
        }

        return $this->getResponse()->representJson(
            $this->_objectManager->get(Data::class)->jsonEncode($result)
        );
    }

    /**
     * Extract address from request
     *
     * @return AddressInterface
     * @throws Exception
     */
    protected function _extractAddress()
    {
        $existingAddressData = $this->getExistingAddressData();

        /** @var Form $addressForm */
        $addressForm = $this->_formFactory->create(
            'customer_address',
            'customer_address_edit',
            $existingAddressData
        );
        $addressData = $addressForm->extractData($this->getRequest());
        $attributeValues = $addressForm->compactData($addressData);

        $this->updateRegionData($attributeValues);

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($existingAddressData, $attributeValues),
            AddressInterface::class
        );
        $addressDataObject->setCustomerId($this->_getSession()->getCustomerId());

        return $addressDataObject;
    }

    /**
     * Retrieve existing address data
     *
     * @return array
     * @throws Exception
     */
    protected function getExistingAddressData()
    {
        $existingAddressData = [];
        if ($addressId = $this->getRequest()->getParam('customer_address_id')) {
            $existingAddress = $this->_addressRepository->getById($addressId);
            if ($existingAddress->getCustomerId() !== $this->_getSession()->getCustomerId()) {
                throw new Exception();
            }
            $existingAddressData = $this->getCustomerAddressMapper()->toFlatArray($existingAddress);
        }
        return $existingAddressData;
    }

    /**
     * Retrieve customer session object
     *
     * @return Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Get Customer Address Mapper instance
     *
     * @return Mapper
     *
     * @deprecated 100.1.3
     */
    private function getCustomerAddressMapper()
    {
        if ($this->customerAddressMapper === null) {
            $this->customerAddressMapper = ObjectManager::getInstance()->get(
                Mapper::class
            );
        }
        return $this->customerAddressMapper;
    }

    /**
     * Update region data
     *
     * @param array $attributeValues
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function updateRegionData(&$attributeValues)
    {
        if (!empty($attributeValues['region_id'])) {
            $newRegion = $this->regionFactory->create()->load($attributeValues['region_id']);
            $attributeValues['region_code'] = $newRegion->getCode();
            $attributeValues['region'] = $newRegion->getDefaultName();
        }

        if ($this->getRequest()->getParam('country_id') !== 'TH') {
            $regionRequestData = $this->getRequest()->getParam('region');
            if ($regionRequestData) {
                $attributeValues['region'] = $regionRequestData;
            }
        }

        $regionData = [
            RegionInterface::REGION_ID => !empty($attributeValues['region_id']) ? $attributeValues['region_id'] : null,
            RegionInterface::REGION => !empty($attributeValues['region']) ? $attributeValues['region'] : null,
            RegionInterface::REGION_CODE => !empty($attributeValues['region_code'])
                ? $attributeValues['region_code']
                : null,
        ];

        $region = $this->regionDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            RegionInterface::class
        );
        $attributeValues['region'] = $region;
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
        $cityId = $this->getRequest()->getParam('city_id');
        if (!empty($cityId)) {
            $newCity = $this->cityFactory->create()->load($cityId);
            $attributeValues['city_id'] = $cityId;
            $attributeValues['city'] = $newCity->getName();
            $attributeValues['city_code'] = $newCity->getCode();
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
        $subdistrictId = $this->getRequest()->getParam('subdistrict_id');
        if (!empty($subdistrictId)) {
            $newSubdistrict = $this->subdistrictFactory->create()->load($subdistrictId);
            $attributeValues['subdistrict_id'] = $subdistrictId;
            $attributeValues['subdistrict'] = $newSubdistrict->getName();
            $attributeValues['subdistrict_code'] = $newSubdistrict->getCode();
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
