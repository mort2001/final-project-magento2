<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Block\Adminhtml\Sales\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Ui\Component\Form\AttributeMapper;

/**
 * Class UpdateAddress
 * @package Tigren\CustomAddress\Block\Adminhtml\Sales\Order\View
 */
class UpdateAddress extends \Magento\Backend\Block\Template
{
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
     * Edit constructor.
     * @param Context $context
     * @param FormKey $formKey
     * @param Data $directoryHelper
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param CollectionFactory $regionCollectionFactory
     * @param \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollection
     * @param \Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory $subdistrictCollection
     * @param AddressInterfaceFactory $addressDataFactory
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param AttributeMapper $attributeMapper
     * @param AttributeMerger $merger
     * @param array $data
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Data $directoryHelper,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        CollectionFactory $regionCollectionFactory,
        \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollection,
        \Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory $subdistrictCollection,
        AddressInterfaceFactory $addressDataFactory,
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMerger $merger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
        $this->_isScopePrivate = true;
        $this->addressDataFactory = $addressDataFactory;
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->cityCollectionFactory = $cityCollection;
        $this->subdistrictCollectionFactory = $subdistrictCollection;
        $this->directoryHelper = $directoryHelper;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getJsLayout()
    {
        $addressElements = $this->getAddressAttributes();

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
                    'config' => [
                        'popUpForm' => [
                            'element' => '#address-form-container',
                            'options' => [
                                'type' => 'popup',
                                'responsive' => true,
                                'innerScroll' => true,
                                'title' => 'Update Order Addresses',
                                'trigger' => 'address-form-container',
                                'buttons' => [
                                    'save' => [
                                        'text' => __('Update Addresses'),
                                        'class' => 'action primary action-update-address'
                                    ],
                                    'cancel' => [
                                        'text' => __('Cancel'),
                                        'class' => 'action secondary action-hide-popup'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'provider' => 'customerProvider',
                    'children' => [
                        'shipping-address-edit-fieldset' => $this->getShippingAddressComponents($addressElements),
                        'billing-address-edit-fieldset' => $this->getBillingAddressComponents($addressElements)
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
     * Prepare shipping address field for the form
     *
     * @param $elements
     * @return array
     */
    public function getShippingAddressComponents($elements)
    {
        $providerName = 'customerProvider';
        $address = $this->getAddress();

        $components = [
            'component' => 'uiComponent',
            'displayArea' => 'shipping-address-edit-fieldsets',
            'children' => $this->merger->merge(
                $elements,
                $providerName,
                'shippingAddress',
                [
                    'entity_id' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'shippingAddress.entity_id',
                        'label' => __('Address ID'),
                        'provider' => 'customerProvider',
                        'visible' => false,
                        'sortOrder' => 0
                    ],
                    'parent_id' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'shippingAddress.parent_id',
                        'label' => __('Order ID'),
                        'provider' => 'customerProvider',
                        'visible' => false,
                        'sortOrder' => 0
                    ],
                    'telephone' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'shippingAddress.telephone',
                        'label' => __('Phone Number'),
                        'provider' => 'customerProvider',
                        'validation' => [
                            'required-entry' => true,
                            'validate-phone-number' => true
                        ],
                        'visible' => true,
                        'sortOrder' => 50
                    ],
                    'fax' => [
                        'visible' => false,
                        'validation' => [
                            'min_text_length' => 0
                        ],
                        'sortOrder' => 120
                    ],
                    'country_id' => [
                        'visible' => false,
                        'sortOrder' => 200
                    ],
                    'region' => [
                        'visible' => false,
                        'sortOrder' => 221
                    ],
                    'region_id' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/region',
                        'config' => [
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'shippingAddress.region',
                            'additionalClasses' => 'shipping-address-region'
                        ],
                        'validation' => [
                            'validate-province-address' => true
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
                            'customScope' => 'shippingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'shippingAddress.city',
                        'label' => __('City'),
                        'provider' => 'customerProvider',
                        'validation' => [
                            'validate-district-address' => 1,
                            'max_text_length' => 255,
                            'min_text_length' => 1
                        ],
                        'visible' => false,
                        'sortOrder' => 216
                    ],
                    'city_id' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/city',
                        'config' => [
                            'customScope' => 'shippingAddress.custom_attributes',
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'shippingAddress.city',
                            'additionalClasses' => 'shipping-address-city'
                        ],
                        'validation' => [
                            'validate-district-address' => true
                        ],
                        'filterBy' => [
                            'target' => '${ $.provider }:${ $.parentScope }.region_id',
                            'field' => 'region_id'
                        ],
                        'sortOrder' => 215
                    ],
                    'subdistrict' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'shippingAddress.custom_attributes',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'label' => __('City'),
                        'provider' => 'customerProvider',
                        'validation' => [
                            'validate-subdistrict-address' => 1,
                            'max_text_length' => 255,
                            'min_text_length' => 1
                        ],
                        'visible' => false,
                        'sortOrder' => 211
                    ],
                    'subdistrict_id' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/subdistrict',
                        'config' => [
                            'customScope' => 'shippingAddress.custom_attributes',
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'shippingAddress.subdistrict',
                            'additionalClasses' => 'shipping-address-subdistrict'
                        ],
                        'validation' => [
                            'validate-subdistrict-address' => true
                        ],
                        'filterBy' => [
                            'target' => '${ $.provider }:${ $.parentScope }.city_id',
                            'field' => 'city_id'
                        ],
                        'sortOrder' => 210
                    ],
                    'postcode' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/post-code',
                        'config' => [
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'shippingAddress.postcode',
                            'additionalClasses' => 'shippingAddress-postcode'
                        ],
                        'validation' => [
                            'validate-postcode-address' => true
                        ],
                        'sortOrder' => 225
                    ]
                ]
            )
        ];

        if (isset($components['children']['street']['children'][0])) {
            // Remove default validation
            unset($components['children']['street']['children'][0]['validation']['required-entry']);
            // Add new validation
            $components['children']['street']['children'][0]['validation']['validate-street-address'] = 1;
        }

        if (isset($components['children']['street']['children'][1])) {
            // Remove default validation
            unset($components['children']['street']['children'][1]['validation']['required-entry']);
            // Add new validation
            $components['children']['street']['children'][1]['validation']['validate-street-address'] = 1;
        }

        if (isset($components['children']['company'])) {
            unset($components['children']['company']);
        }

        if (isset($components['children'])) {
            foreach ($components['children'] as $key => $value) {
                $components['children'][$key]['config']['additionalClasses'] = 'shipping-address-' . $key;

                if ($key === 'city_id' || $key === 'subdistrict' || $key === 'subdistrict_id') {
                    $components['children'][$key]['dataScope'] = 'shippingAddress.custom_attributes.' . $key;
                }

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children'][$key]['options']);
                    $components['children'][$key]['deps'] = [$providerName];
                    $components['children'][$key]['imports'] = [
                        'initialOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key,
                        'setOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key
                    ];
                }
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
     * Prepare billing address field for the form
     *
     * @param $elements
     * @return array
     */
    public function getBillingAddressComponents($elements)
    {
        $providerName = 'customerProvider';
        $address = $this->getAddress();

        $components = [
            'component' => 'uiComponent',
            'displayArea' => 'billing-address-edit-fieldsets',
            'children' => $this->merger->merge(
                $elements,
                $providerName,
                'billingAddress',
                [
                    'entity_id' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'billingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'billingAddress.entity_id',
                        'label' => __('Address ID'),
                        'provider' => 'customerProvider',
                        'visible' => false,
                        'sortOrder' => 0
                    ],
                    'parent_id' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'billingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'billingAddress.parent_id',
                        'label' => __('Order ID'),
                        'provider' => 'customerProvider',
                        'visible' => false,
                        'sortOrder' => 0
                    ],
                    'telephone' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'billingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'billingAddress.telephone',
                        'label' => __('Phone Number'),
                        'provider' => 'customerProvider',
                        'validation' => [
                            'required-entry' => true,
                            'validate-phone-number' => true
                        ],
                        'visible' => true,
                        'sortOrder' => 50
                    ],
                    'fax' => [
                        'visible' => false,
                        'validation' => [
                            'min_text_length' => 0
                        ],
                        'sortOrder' => 120
                    ],
                    'country_id' => [
                        'visible' => false,
                        'sortOrder' => 200
                    ],
                    'region' => [
                        'visible' => false,
                        'sortOrder' => 221
                    ],
                    'region_id' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/region',
                        'config' => [
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'billingAddress.region',
                            'additionalClasses' => 'billing-address-region'
                        ],
                        'validation' => [
                            'validate-province-address' => true
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
                            'customScope' => 'billingAddress',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'dataScope' => 'billingAddress.city',
                        'label' => __('City'),
                        'provider' => 'customerProvider',
                        'validation' => [
                            'validate-district-address' => 1,
                            'max_text_length' => 255,
                            'min_text_length' => 1
                        ],
                        'visible' => false,
                        'sortOrder' => 216
                    ],
                    'city_id' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/city',
                        'config' => [
                            'customScope' => 'billingAddress.custom_attributes',
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'billingAddress.city',
                            'additionalClasses' => 'billing-address-city'
                        ],
                        'validation' => [
                            'validate-district-address' => true
                        ],
                        'filterBy' => [
                            'target' => '${ $.provider }:${ $.parentScope }.region_id',
                            'field' => 'region_id'
                        ],
                        'sortOrder' => 215
                    ],
                    'subdistrict' => [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'billingAddress.custom_attributes',
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input'
                        ],
                        'label' => __('City'),
                        'provider' => 'customerProvider',
                        'validation' => [
                            'validate-subdistrict-address' => 1,
                            'max_text_length' => 255,
                            'min_text_length' => 1
                        ],
                        'visible' => false,
                        'sortOrder' => 211
                    ],
                    'subdistrict_id' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/subdistrict',
                        'config' => [
                            'customScope' => 'billingAddress.custom_attributes',
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'billingAddress.subdistrict',
                            'additionalClasses' => 'billing-address-subdistrict'
                        ],
                        'validation' => [
                            'validate-subdistrict-address' => true
                        ],
                        'filterBy' => [
                            'target' => '${ $.provider }:${ $.parentScope }.city_id',
                            'field' => 'city_id'
                        ],
                        'sortOrder' => 210
                    ],
                    'postcode' => [
                        'component' => 'Tigren_CustomAddress/js/form/element/directory/post-code',
                        'config' => [
                            'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                            'customEntry' => 'billingAddress.postcode',
                            'additionalClasses' => 'billing-address-postcode'
                        ],
                        'validation' => [
                            'validate-postcode-address' => true
                        ],
                        'sortOrder' => 225
                    ],
                ]
            )
        ];

        if (isset($components['children']['street']['children'][0])) {
            // Remove default validation
            unset($components['children']['street']['children'][0]['validation']['required-entry']);
            // Add new validation
            $components['children']['street']['children'][0]['validation']['validate-street-address'] = 1;
        }

        if (isset($components['children']['street']['children'][1])) {
            // Remove default validation
            unset($components['children']['street']['children'][1]['validation']['required-entry']);
            // Add new validation
            $components['children']['street']['children'][1]['validation']['validate-street-address'] = 1;
        }

        if (isset($components['children']['company'])) {
            unset($components['children']['company']);
        }

        if (isset($components['children'])) {
            foreach ($components['children'] as $key => $value) {
                $components['children'][$key]['config']['additionalClasses'] = 'billing-address-' . $key;

                if ($key === 'city_id' || $key === 'subdistrict' || $key === 'subdistrict_id') {
                    $components['children'][$key]['dataScope'] = 'billingAddress.custom_attributes.' . $key;
                }

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children'][$key]['options']);
                    $components['children'][$key]['deps'] = [$providerName];
                    $components['children'][$key]['imports'] = [
                        'initialOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key,
                        'setOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key
                    ];
                }
            }
        }

        return $components;
    }

    /**
     * @return string
     */
    public function getAddressesDataUrl()
    {
        return $this->getUrl('custom_address/sales_order/addressData');
    }

    /**
     * @return string
     */
    public function updateAddressesDataUrl()
    {
        return $this->getUrl('custom_address/sales_order/updateAddress');
    }

    /**
     * @return $this|Template
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->_address = $this->addressDataFactory->create();

        return $this;
    }
}
