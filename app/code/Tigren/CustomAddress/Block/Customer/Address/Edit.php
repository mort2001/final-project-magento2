<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Block\Customer\Address;

use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Ui\Component\Form\AttributeMapper;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;
use Tigren\CustomAddress\Model\Config\Source\SuggestionType;

/**
 * Class Edit
 * @package Tigren\CustomAddress\Block\Customer\Address
 */
class Edit extends Template
{
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var AttributeMapper
     */
    protected $attributeMapper;

    /**
     * @var AttributeMerger
     */
    protected $merger;

    /**
     * @var AddressInterface|null
     */
    protected $_address = null;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var array
     */
    private $countryOptions;

    /**
     * @var array
     */
    private $regionOptions;

    /**
     * @var CollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var CollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @var array
     */
    private $cityOptions;

    /**
     * @var array
     */
    private $subdistrictOptions;

    /**
     * @var \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory
     */
    private $cityCollectionFactory;

    /**
     * @var \Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory
     */
    private $subdistrictCollectionFactory;

    /**
     * @var Data
     */
    private $directoryHelper;

    /**
     * @var CustomAddressHelper
     */
    private $customAddressHelper;

    /**
     * Edit constructor.
     * @param Context $context
     * @param FormKey $formKey
     * @param Data $directoryHelper
     * @param \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollection
     * @param \Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory $subdistrictCollection
     * @param Session $customerSession
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CurrentCustomer $currentCustomer
     * @param DataObjectHelper $dataObjectHelper
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param AttributeMapper $attributeMapper
     * @param AttributeMerger $merger
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection
     * @param CollectionFactory $regionCollection
     * @param CustomAddressHelper $customAddressHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Data $directoryHelper,
        \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollection,
        \Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory $subdistrictCollection,
        Session $customerSession,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        CurrentCustomer $currentCustomer,
        DataObjectHelper $dataObjectHelper,
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMerger $merger,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection,
        CollectionFactory $regionCollection,
        CustomAddressHelper $customAddressHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
        $this->_customerSession = $customerSession;
        $this->_addressRepository = $addressRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->currentCustomer = $currentCustomer;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->countryCollectionFactory = $countryCollection;
        $this->regionCollectionFactory = $regionCollection;
        $this->cityCollectionFactory = $cityCollection;
        $this->subdistrictCollectionFactory = $subdistrictCollection;
        $this->directoryHelper = $directoryHelper;
        $this->customAddressHelper = $customAddressHelper;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getJsLayout()
    {
        $addressElements = $this->getAddressAttributes();
        $customerElements = $this->getCustomerAttributes();

        $jsLayout = [
            'components' => [
                'customerProvider' => [
                    'component' => 'uiComponent',
                    'dictionaries' => [
                        'country_id' => $this->getCountryOptions(),
                        'region_id' => $this->getRegionOptions(),
                    ],
                    'additional_dictionaries' => [
                        'city_id' => $this->getCityOptions(),
                        'subdistrict_id' => $this->getSubdistrictOptions(),
                        'postcode' => $this->getSubdistrictOptions()
                    ]
                ],
                'customer-address-edit-form' => [
                    'component' => 'Tigren_CustomAddress/js/customer/address/edit-form',
                    'provider' => 'customerProvider',
                    'children' => [
                        'address-contact-fieldset' => $this->getContactComponents($customerElements),
                        'address-edit-fieldset' => $this->getAddressComponents($addressElements)
                    ]
                ]
            ],
            'types' => [
                'form.input' => [
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'provider' => 'customerProvider',
                        'deps' => ['customerProvider'],
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/input'
                    ]
                ]
            ]
        ];

        return json_encode($jsLayout, JSON_HEX_TAG);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getAddressAttributes()
    {
        /** @var AttributeInterface[] $attributes */
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_register_address'
        );

        $elements = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if ($attribute->getIsUserDefined()) {
                continue;
            }
            $elements[$code] = $this->attributeMapper->map($attribute);
            if (isset($elements[$code]['label'])) {
                $label = $elements[$code]['label'];
                $elements[$code]['label'] = __($label);
            }
        }

        return $elements;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getCustomerAttributes()
    {
        $allowedAttributeCodes = ['firstname', 'lastname'];

        /** @var AttributeInterface[] $attributes */
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer',
            'customer_account_edit'
        );

        $elements = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if ($attribute->getIsUserDefined() || !in_array($code, $allowedAttributeCodes)) {
                continue;
            }
            $elements[$code] = $this->attributeMapper->map($attribute);
            if (isset($elements[$code]['label'])) {
                $label = $elements[$code]['label'];
                $elements[$code]['label'] = __($label);
            }
        }

        return $elements;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    private function getCountryOptions()
    {
        if (!isset($this->countryOptions)) {
            $this->countryOptions = $this->countryCollectionFactory->create()->loadByStore(
                $this->_storeManager->getStore()->getId()
            )->toOptionArray();
            $this->countryOptions = $this->orderCountryOptions($this->countryOptions);
        }

        return $this->countryOptions;
    }

    /**
     * Sort country options by top country codes.
     *
     * @param array $countryOptions
     * @return array
     */
    private function orderCountryOptions(array $countryOptions)
    {
        $topCountryCodes = $this->directoryHelper->getTopCountryCodes();
        if (empty($topCountryCodes)) {
            return $countryOptions;
        }

        $headOptions = [];
        $tailOptions = [
            [
                'value' => 'delimiter',
                'label' => '──────────',
                'disabled' => true,
            ]
        ];
        foreach ($countryOptions as $countryOption) {
            if (empty($countryOption['value']) || in_array($countryOption['value'], $topCountryCodes)) {
                array_push($headOptions, $countryOption);
            } else {
                array_push($tailOptions, $countryOption);
            }
        }
        return array_merge($headOptions, $tailOptions);
    }

    /**
     * Get region options list.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getRegionOptions()
    {
        if (!isset($this->regionOptions)) {
            $this->regionOptions = $this->regionCollectionFactory->create()->addAllowedCountriesFilter(
                $this->_storeManager->getStore()->getId()
            )->toOptionArray();
        }

        return $this->regionOptions;
    }

    /**
     * Get city options list.
     *
     * @return array
     */
    private function getCityOptions()
    {
        if (!isset($this->cityOptions)) {
            $this->cityOptions = $this->cityCollectionFactory->create()->toOptionArray();
        }

        return $this->cityOptions;
    }

    /**
     * Get sub district options list.
     *
     * @return array
     */
    private function getSubdistrictOptions()
    {
        if (!isset($this->subdistrictOptions)) {
            $this->subdistrictOptions = $this->subdistrictCollectionFactory->create()->toOptionArray();
        }

        return $this->subdistrictOptions;
    }

    /**
     *
     * Prepare contact field for the form
     *
     * @param $elements
     * @return array
     */
    public function getContactComponents($elements)
    {
        $providerName = 'customerProvider';

        $components = [
            'component' => 'uiComponent',
            'displayArea' => 'address-contact-fieldsets',
            'children' => $this->merger->merge(
                $elements,
                $providerName,
                'address',
                [
                    'telephone' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'address',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'address.telephone',
                        'label' => __('Phone Number'),
                        'provider' => 'customerProvider',
                        'validation' => [
                            'required-entry' => true,
                            'validate-phone-number' => true
                        ],
                        'visible' => true,
                        'sortOrder' => 61
                    ],
                    'company' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'address',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'address.company',
                        'label' => __('Company'),
                        'provider' => 'customerProvider',
                        'visible' => true,
                        'sortOrder' => 62
                    ]
                ]
            )
        ];

        $address = $this->getAddress();

        if (isset($components['children']['telephone'])) {
            $components['children']['telephone']['value'] = $address && $address->getTelephone()
                ? $address->getTelephone() : null;
        }

        if (isset($components['children']['company'])) {
            $components['children']['company']['value'] = $address && $address->getCompany()
                ? $address->getCompany() : null;
        }
        if (isset($components['children']['default_shipping'])) {
            if ($this->canSetAsDefaultShipping()) {
                $components['children']['default_shipping']['value'] = $this->isDefaultShipping();
            } else {
                $components['children']['default_shipping']['value'] = true;
                $components['children']['default_shipping']['visible'] = false;
            }
        }

        return $components;
    }

    /**
     * Return the associated address.
     *
     * @return AddressInterface
     */
    public function getAddress()
    {
        return $this->_address;
    }

    /**
     * Prepare address field for the form
     *
     * @param $elements
     * @return array
     */
    public function getAddressComponents($elements)
    {
        $providerName = 'customerProvider';
        $address = $this->getAddress();
        $suggestionType = $this->customAddressHelper->getSuggestionType();

        if ($suggestionType == SuggestionType::SUGGESTION_TYPE_DROP_DOWN) {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'address-edit-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'address',
                    [
                        'id' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.id',
                            'label' => __('Address ID'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'value' => $this->getRequest()->getParam('id'),
                            'sortOrder' => 0
                        ],
                        'firstname' => [
                            'visible' => false,
                        ],
                        'lastname' => [
                            'visible' => false,
                        ],
                        'telephone' => [
                            'visible' => false,
                            'validation' => [
                                'validate-phone-number' => true
                            ]
                        ],
                        'company' => [
                            'visible' => false,
                        ],
                        'fax' => [
                            'visible' => false,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 120
                        ],
                        'country_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/country',
                            'sortOrder' => 200
                        ],
                        'region' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.region',
                            'label' => __('State/Province'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'validation' => [
                                'required-entry' => true,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'sortOrder' => 210
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/region',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'customEntry' => 'address.region',
                                'additionalClasses' => 'address-region'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                'field' => 'country_id'
                            ],
                            'sortOrder' => 220
                        ],
                        'city' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.city',
                            'label' => __('City'),
                            'provider' => 'customerProvider',
                            'validation' => [
                                'max_text_length' => 255,
                                'min_text_length' => 1,
                                'required-entry' => true
                            ],
                            'visible' => false,
                            'sortOrder' => 230
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/city',
                            'config' => [
                                'customScope' => 'address.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'address-city'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:address.region_id',
                                'field' => 'region_id'
                            ],
                            'sortOrder' => 240
                        ],
                        'subdistrict' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.custom_attributes.subdistrict',
                            'label' => __('Subdistrict'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'sortOrder' => 250,
                            'validation' => [
                                'required-entry' => true
                            ]
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/subdistrict',
                            'config' => [
                                'customScope' => 'address.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'address-subdistrict'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.city_id',
                                'field' => 'city_id'
                            ],
                            'sortOrder' => 260
                        ],
                        'postcode' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/post-code',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'customEntry' => 'address.postcode',
                                'additionalClasses' => 'address-postcode'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'sortOrder' => 270
                        ],
                        'default_shipping' => [
                            'component' => 'Magento_Ui/js/form/element/boolean',
                            'config' => [
                                'template' => 'ui/form/element/checkbox',
                                'additionalClasses' => 'address-default-shipping'
                            ],
                            'provider' => 'customerProvider',
                            'dataScope' => 'address.default_shipping',
                            'label' => __('Default shipping address'),
                            'sortOrder' => 280
                        ],
                        'default_billing' => [
                            'component' => 'Magento_Ui/js/form/element/boolean',
                            'config' => [
                                'template' => 'ui/form/element/checkbox',
                                'additionalClasses' => 'address-default-billing'
                            ],
                            'provider' => 'customerProvider',
                            'dataScope' => 'address.default_billing',
                            'label' => __('Default billing address'),
                            'sortOrder' => 290
                        ]
                    ]
                )
            ];
        } else {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'address-edit-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'address',
                    [
                        'id' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.id',
                            'label' => __('Address ID'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'value' => $this->getRequest()->getParam('id'),
                            'sortOrder' => 0
                        ],
                        'firstname' => [
                            'visible' => false,
                        ],
                        'lastname' => [
                            'visible' => false,
                        ],
                        'telephone' => [
                            'visible' => false,
                            'validation' => [
                                'validate-phone-number' => true
                            ]
                        ],
                        'company' => [
                            'visible' => false,
                        ],
                        'fax' => [
                            'visible' => false,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 120
                        ],
                        'country_id' => [
                            'sortOrder' => 200
                        ],
                        'region' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.region',
                            'label' => __('Region/State'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'sortOrder' => 210
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/region',
                            'config' => [
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'customEntry' => 'address.region',
                                'additionalClasses' => 'address-region'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                'field' => 'country_id'
                            ],
                            'sortOrder' => 220
                        ],
                        'city' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.city',
                            'label' => __('City'),
                            'provider' => 'customerProvider',
                            'validation' => [
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 230
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/city',
                            'config' => [
                                'customScope' => 'address.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'customEntry' => 'address.city',
                                'additionalClasses' => 'address-city'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.region_id',
                                'field' => 'region_id'
                            ],
                            'sortOrder' => 240
                        ],
                        'subdistrict' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'address.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'address.custom_attributes.subdistrict',
                            'label' => __('City'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'sortOrder' => 250
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/subdistrict',
                            'config' => [
                                'customScope' => 'address.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'customEntry' => 'address.subdistrict',
                                'additionalClasses' => 'address-subdistrict'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.city_id',
                                'field' => 'city_id'
                            ],
                            'sortOrder' => 260
                        ],
                        'postcode' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/post-code',
                            'config' => [
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'customEntry' => 'address.postcode',
                                'additionalClasses' => 'address-postcode'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'sortOrder' => 270
                        ],
                        'default_shipping' => [
                            'component' => 'Magento_Ui/js/form/element/boolean',
                            'config' => [
                                'template' => 'ui/form/element/checkbox',
                                'additionalClasses' => 'address-default-shipping'
                            ],
                            'provider' => 'customerProvider',
                            'dataScope' => 'address.default_shipping',
                            'label' => __('Default shipping address.'),
                            'sortOrder' => 280
                        ],
                        'default_billing' => [
                            'component' => 'Magento_Ui/js/form/element/boolean',
                            'config' => [
                                'template' => 'ui/form/element/checkbox',
                                'additionalClasses' => 'address-default-billing'
                            ],
                            'provider' => 'customerProvider',
                            'dataScope' => 'address.default_billing',
                            'label' => __('Default billing address.'),
                            'sortOrder' => 290
                        ]
                    ]
                )
            ];
        }
        $streetData = $address->getStreet();

        if (isset($components['children']['street']['children'][0])) {
            $components['children']['street']['label'] = 'Address';
            // Add new validation
            $components['children']['street']['children'][0]['value'] = $streetData && isset($streetData[0]) ? $streetData[0] : null;
        }

        if (isset($components['children']['street']['children'][1])) {
            // Add new validation
            $components['children']['street']['children'][1]['value'] = $streetData && isset($streetData[1]) ? $streetData[1] : null;
        }

        if ($suggestionType == SuggestionType::SUGGESTION_TYPE_DROP_DOWN && isset($components['children']['region_id'])) {
            $components['children']['region_id']['value'] = $address && $address->getRegion()
                ? $address->getRegion()->getRegionId() : null;
        }

        if (isset($components['children'])) {
            foreach ($components['children'] as $key => $value) {
                $components['children'][$key]['config']['additionalClasses'] = 'address-' . $key;

//                if ($key === 'country_id') {
//                    $components['children'][$key]['value'] = $address && $address->getCountryId() ? $address->getCountryId() : 'TH';
//                }

                if ($key === 'region') {
                    if ($address->getRegion()) {
                        if ($address->getRegion()->getRegion()) {
                            $components['children'][$key]['value'] = $address->getRegion()->getRegion();
                        }
                    }
                }
                if ($key === 'city') {
                    $components['children'][$key]['sortOrder'] = 240;
                    if ($address->getCity() && empty($address->getExtensionAttributes()->getCityId())) {
                        $components['children'][$key]['visible'] = true;
                        $components['children'][$key]['value'] = $address->getCity();
                        $components['children']['city_id']['visible'] = false;
                    }
                }
                if ($key === 'subdistrict') {
                    $components['children'][$key]['sortOrder'] = 250;
                    if ($address->getSubdistrict() &&  empty($address->getExtensionAttributes()->getSubdistrictId())) {
                        $components['children'][$key]['visible'] = true;
                        $components['children'][$key]['value'] = $address->getSubdistrict();
                        $components['children']['subdistrict_id']['visible'] = false;
                    }
                }

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children'][$key]['options']);
                    $components['children'][$key]['deps'] = [$providerName];
                    $components['children'][$key]['imports'] = [
                        'initialOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key,
                        'setOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key
                    ];
                    if ($key === 'city_id' || $key === 'subdistrict_id') {
                        $components['children'][$key]['dataScope'] = 'address.custom_attributes.' . $key;
                    }

                    if ($key === 'city_id') {
                        $components['children'][$key]['sortOrder'] = 240;
                        $components['children'][$key]['config']['defaultValue'] = $address && $address->getExtensionAttributes()->getCityId()
                            ? $address->getExtensionAttributes()->getCityId() : null;
                        $components['children'][$key]['value'] = $address && $address->getExtensionAttributes()->getCityId()
                            ? $address->getExtensionAttributes()->getCityId() : null;
                    }

                    if ($key === 'subdistrict_id') {
                        $components['children'][$key]['sortOrder'] = 250;
                        $components['children'][$key]['value'] = $address && $address->getExtensionAttributes()->getSubdistrictId()
                            ? $address->getExtensionAttributes()->getSubdistrictId() : null;
                        $components['children'][$key]['config']['defaultValue'] = $address && $address->getExtensionAttributes()->getSubdistrictId()
                            ? $address->getExtensionAttributes()->getSubdistrictId() : null;
                    }

                    if ($key === 'postcode') {
                        $components['children'][$key]['value'] = $address && $address->getPostcode() ? $address->getPostcode() : null;
                    }
                }
            }
        }

        if (isset($components['children']['default_shipping'])) {
            if ($this->canSetAsDefaultShipping()) {
                $components['children']['default_shipping']['value'] = $this->isDefaultShipping();
            } else {
                $components['children']['default_shipping']['value'] = true;
                $components['children']['default_shipping']['visible'] = false;
            }
        }

        if (isset($components['children']['default_billing'])) {
            if ($this->canSetAsDefaultBilling()) {
                $components['children']['default_billing']['value'] = $this->isDefaultBilling();
            } else {
                $components['children']['default_billing']['value'] = true;
                $components['children']['default_billing']['visible'] = false;
            }
        }

        return $components;
    }

    /**
     * Determine if the address can be set as the default shipping address.
     *
     * @return bool|int
     */
    public function canSetAsDefaultShipping()
    {
        if (!$this->getAddress()->getId()) {
            return $this->getCustomerAddressCount();
        }
        return !$this->isDefaultShipping();
    }

    /**
     * Retrieve the number of addresses associated with the customer given a customer Id.
     *
     * @return int
     */
    public function getCustomerAddressCount()
    {
        return count($this->getCustomer()->getAddresses());
    }

    /**
     * Retrieve the Customer Data using the customer Id from the customer session.
     *
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        return $this->currentCustomer->getCustomer();
    }

    /**
     * Is the address the default shipping address?
     *
     * @return bool
     */
    public function isDefaultShipping()
    {
        return (bool)$this->getAddress()->isDefaultShipping();
    }

    /**
     * Determine if the address can be set as the default billing address.
     *
     * @return bool|int
     */
    public function canSetAsDefaultBilling()
    {
        if (!$this->getAddress()->getId()) {
            return $this->getCustomerAddressCount();
        }
        return !$this->isDefaultBilling();
    }

    /**
     * Is the address the default billing address?
     *
     * @return bool
     */
    public function isDefaultBilling()
    {
        return (bool)$this->getAddress()->isDefaultBilling();
    }

    /**
     * Retrieve form key
     *
     * @return string
     * @throws LocalizedException
     * @codeCoverageIgnore
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get base url for block.
     *
     * @return string
     * @throws NoSuchEntityException
     * @codeCoverageIgnore
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return $this|Template
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        // Init address object
        if ($addressId = $this->getRequest()->getParam('id')) {
            try {
                $this->_address = $this->_addressRepository->getById($addressId);
                if ($this->_address->getCustomerId() != $this->_customerSession->getCustomerId()) {
                    $this->_address = null;
                }
            } catch (NoSuchEntityException $e) {
                $this->_address = null;
            }
        }

        if ($this->_address === null || !$this->_address->getId()) {
            $this->_address = $this->addressDataFactory->create();
            $customer = $this->getCustomer();
            $this->_address->setPrefix($customer->getPrefix());
            $this->_address->setFirstname($customer->getFirstname());
            $this->_address->setMiddlename($customer->getMiddlename());
            $this->_address->setLastname($customer->getLastname());
            $this->_address->setSuffix($customer->getSuffix());
        }

        $this->pageConfig->getTitle()->set($this->getTitle());

        if ($postedData = $this->_customerSession->getAddressFormData(true)) {
            $postedData['region'] = [
                'region' => $postedData['region'] ?: null,
            ];
            if (!empty($postedData['region_id'])) {
                $postedData['region']['region_id'] = $postedData['region_id'];
            }
            $this->dataObjectHelper->populateWithArray(
                $this->_address,
                $postedData,
                AddressInterface::class
            );
        }

        return $this;
    }

    /**
     * Return the title, either editing an existing address, or adding a new one.
     *
     * @return string
     */
    public function getTitle()
    {
        if ($title = $this->getData('title')) {
            return $title;
        }
        if ($this->getAddress()->getId()) {
            $title = __('Edit Address');
        } else {
            $title = __('Add New Address');
        }
        return $title;
    }
}
