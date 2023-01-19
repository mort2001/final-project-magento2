<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Block\Adminhtml\Sales\Order\Create\Shipping;

use Magento\Backend\Block\Template\Context;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Ui\Component\Form\AttributeMapper;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;
use Tigren\CustomAddress\Model\Config\Source\SuggestionType;

/**
 * Class CustomAddress
 * @package Tigren\CustomAddress\Block\Adminhtml\Sales\Order\Create\Shipping
 */
class CustomAddress extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Tigren_CustomAddress::sales/order/create/shipping/custom-address.phtml';

    /**
     * @var bool
     */
    protected $_isScopePrivate = false;

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
     * CustomAddress constructor.
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
     * @param Json|null $serializer
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
        $this->_isScopePrivate = true;
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
     * @return false|string
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
                'shipping-custom-address-edit-form' => [
                    'component' => 'Tigren_CustomAddress/js/sales/order/create/shipping-address',
                    'provider' => 'customerProvider',
                    'children' => [
                        'shipping-address-edit-fieldset' => $this->getShippingAddressComponents($addressElements)
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

        $ignoreAttributes = [
            'prefix',
            'firstname',
            'middlename',
            'lastname',
            'suffix',
            'company',
            'street',
            'telephone',
            'fax',
            'vat_id'
        ];

        $elements = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if ($attribute->getIsUserDefined() || in_array($code, $ignoreAttributes)) {
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
     * Get country options list.
     *
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

        $suggestionType = $this->customAddressHelper->getSuggestionType();

        if ($suggestionType == SuggestionType::SUGGESTION_TYPE_DROP_DOWN) {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'shipping-address-edit-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'shippingAddress',
                    [
                        'country_id' => [
                            'sortOrder' => 0,
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/country'
                        ],
                        'region' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/backend/region-text',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'shippingAddress.region',
                            'label' => __('State/Region'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'disabled' => true,
                            'sortOrder' => 10
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/region',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'customEntry' => 'shippingAddress.region',
                                'additionalClasses' => 'shipping-region'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                'field' => 'country_id'
                            ],
                            'sortOrder' => 20
                        ],
                        'city' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/backend/city-text',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'shippingAddress.city',
                            'label' => __('City'),
                            'provider' => 'customerProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 30
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/city',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'shipping-city'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:shippingAddress.region_id',
                                'field' => 'region_id'
                            ],
                            'sortOrder' => 25
                        ],
                        'subdistrict' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/backend/subdistrict-text',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'shippingAddress.custom_attributes.subdistrict',
                            'label' => __('Subdistrict'),
                            'provider' => 'customerProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 40
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/subdistrict',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'shipping-subdistrict'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.city_id',
                                'field' => 'city_id'
                            ],
                            'sortOrder' => 50
                        ],
                        'postcode' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/post-code',
                            'config' => [
                                'customEntry' => 'shippingAddress.postcode',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'additionalClasses' => 'shipping-postcode'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'sortOrder' => 270
                        ]
                    ]
                )
            ];
        } else {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'shipping-address-edit-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'shippingAddress',
                    [
                        'country_id' => [
                            'visible' => false,
                            'sortOrder' => 200
                        ],
                        'region' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'additionalClasses' => 'shipping-address-region'
                            ],
                            'dataScope' => 'shippingAddress.region',
                            'label' => __('Region'),
                            'provider' => 'customerProvider',
                            'visible' => false,
                            'sortOrder' => 30
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/region',
                            'config' => [
                                'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                                'customEntry' => 'shippingAddress.region',
                                'additionalClasses' => 'shipping-address-region_id'
                            ],
                            'validation' => [
                                'validate-province-address' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                'field' => 'country_id'
                            ],
                            'sortOrder' => 20
                        ],
                        'city' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'additionalClasses' => 'shipping-address-city'
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
                            'sortOrder' => 10
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/city',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                                'customEntry' => 'shippingAddress.city',
                                'additionalClasses' => 'shipping-address-city_id'
                            ],
                            'validation' => [
                                'validate-district-address' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.region_id',
                                'field' => 'region_id'
                            ],
                            'sortOrder' => 5
                        ],
                        'subdistrict' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'additionalClasses' => 'shipping-address-subdistrict'
                            ],
                            'label' => __('City'),
                            'provider' => 'customerProvider',
                            'validation' => [
                                'validate-subdistrict-address' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 40
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/subdistrict',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/directory/ui-select',
                                'customEntry' => 'shippingAddress.subdistrict',
                                'additionalClasses' => 'shipping-address-subdistrict_id'
                            ],
                            'validation' => [
                                'validate-subdistrict-address' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.city_id',
                                'field' => 'city_id'
                            ],
                            'sortOrder' => 50
                        ],
                        'postcode' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/post-code',
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
        }

        if (isset($components['children'])) {
            foreach ($components['children'] as $key => $value) {
                $components['children'][$key]['config']['additionalClasses'] = 'shipping-address-' . $key;
                $components['children'][$key]['config']['isBackend'] = true;

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
     * @return string
     */
    public function getAddressesDataUrl()
    {
        return $this->getUrl('omsmnp/orders_validate/addressData');
    }

    /**
     * @return string
     */
    public function updateAddressesDataUrl()
    {
        return $this->getUrl('omsmnp/orders_validate/updateAddress');
    }

    /**
     * @return $this|Template
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->_address = $this->addressDataFactory->create();

        return $this;
    }
}
