<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Checkout\Block;

use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Ui\Component\Form\AttributeMapper;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;
use Tigren\CustomAddress\Model\Config\Source\SuggestionType;
use Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory;

/**
 * Class LayoutProcessor
 * @package Tigren\CustomAddress\Plugin\Magento\Checkout\Block
 */
class LayoutProcessor
{
    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var AttributeMapper
     */
    protected $attributeMapper;

    /**
     * @var AttributeMerger
     */
    protected $merger;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

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
     * @var CollectionFactory
     */
    private $subdistrictCollectionFactory;

    /**
     * @var CustomAddressHelper
     */
    private $customAddressHelper;

    /**
     * @var Data
     */
    private $checkoutDataHelper;

    /**
     * @var null
     */
    public $quote = null;

    /**
     * LayoutProcessor constructor.
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param AttributeMapper $attributeMapper
     * @param AttributeMerger $merger
     * @param CheckoutSession $checkoutSession
     * @param UrlInterface $urlBuilder
     * @param \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollection
     * @param CollectionFactory $subdistrictCollection
     * @param CustomAddressHelper $customAddressHelper
     */
    public function __construct(
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMerger $merger,
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollection,
        CollectionFactory $subdistrictCollection,
        CustomAddressHelper $customAddressHelper
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->cityCollectionFactory = $cityCollection;
        $this->subdistrictCollectionFactory = $subdistrictCollection;
        $this->customAddressHelper = $customAddressHelper;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param $result
     * @param array $jsLayout
     * @return array
     * @throws LocalizedException
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        $result,
        array $jsLayout
    ) {
        $jsLayoutResult = $result;

        // Add dictionaries
        if (!isset($jsLayoutResult['components']['checkoutProvider']['additional_dictionaries'])) {
            $jsLayoutResult['components']['checkoutProvider']['additional_dictionaries']['city_id'] = $this->getCityOptions();
            $jsLayoutResult['components']['checkoutProvider']['additional_dictionaries']['subdistrict_id'] = $this->getSubdistrictOptions();
            $jsLayoutResult['components']['checkoutProvider']['additional_dictionaries']['postcode'] = $this->getSubdistrictOptions();
        }

        $addressElements = $this->getAddressAttributes();
        if (isset($jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children'])) {
            $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset'] = $this->getShippingAddressComponent($addressElements);
        }

        // Billing address at step shipping or payment
        if ($this->customAddressHelper->getMoveBilling() && !$this->getQuote()->isVirtual()) {
            if (isset($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['billing-address-form'])) {
                unset($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['afterMethods']['children']['billing-address-form']);
            }

            $billingAddressForms = $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'];
            if ($billingAddressForms) {
                foreach ($billingAddressForms as $billingAddressFormsKey => $billingAddressForm) {
                    if ($billingAddressFormsKey != 'before-place-order') {
                        unset($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                            ['payment']['children']['payments-list']['children'][$billingAddressFormsKey]);
                    }
                }
            }

            $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['billing-step'] = [
                'component' => 'uiComponent',
                'displayArea' => 'billing-step',
                'provider' => 'checkoutProvider',
                'children' => []
            ];

            $this->_processBillingAddressComponentsInShipping($jsLayoutResult);
            if ($this->customAddressHelper->isFullTaxInvoiceEnabled()) {
                $this->_processFullTaxInvoiceComponents($jsLayoutResult);
            }
        } else {
            if (isset($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children'])) {
                $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children'] = $this->processPaymentChildrenComponents(
                    $jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children'],
                    $addressElements
                );
            }
            if (isset($jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['billingAddress'])) {
                unset($jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
                    ['children']['shippingAddress']['children']['billingAddress']);
            }
        }

        return $jsLayoutResult;
    }

    /**
     * Get Quote
     *
     * @return Quote|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    /**
     * @param $jsLayoutResult
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _processFullTaxInvoiceComponents(&$jsLayoutResult)
    {
        $companyName = $this->getQuote()->getCustomAttribute('company')
            ? $this->getQuote()->getCustomAttribute('company')->getValue() : '';
        $taxIdentificationNumber = $this->getQuote()->getCustomAttribute('tax_identification_number') ? $this->getQuote()->getCustomAttribute('tax_identification_number')->getValue() : '';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['billing-step']['children']['full-tax-invoice'] = [
            'component' => 'Tigren_CustomAddress/js/view/full-tax-invoice',
            'displayArea' => 'full-tax-invoice',
            'provider' => 'checkoutProvider',
            'children' => [
                'before-form' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'before-form',
                    'children' => []
                ],
                'before-fields' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'before-fields',
                    'children' => []
                ],
                'additional-fieldsets' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'additional-fieldsets',
                    'children' => [
                        'use_full_tax' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'dataScope' => 'fullTaxInvoice.use_full_tax',
                            'provider' => 'checkoutProvider',
                            'config' => [
                                'customScope' => 'fullTaxInvoice',
                                'template' => 'ui/form/element/hidden'
                            ]
                        ],
                        'invoice_type' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'label' => __(''),
                            'sortOrder' => 5,
                            'dataScope' => 'fullTaxInvoice.invoice_type',
                            'provider' => 'checkoutProvider',
                            'config' => [
                                'template' => 'ui/form/field',
                                'customScope' => 'fullTaxInvoice',
                                'elementTmpl' => 'Tigren_CustomAddress/full-tax-invoice/type',
                                'additionalClasses' => 'tax-invoice-label-options'
                            ]
                        ],
                        'tax_identification_number' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'label' => __('Tax Identification Number'),
                            'sortOrder' => 6,
                            'dataScope' => 'fullTaxInvoice.tax_identification_number',
                            'provider' => 'checkoutProvider',
                            'value' => $taxIdentificationNumber,
                            'visible' => false,
                            'disabled' => false,
                            'config' => [
                                'customScope' => 'fullTaxInvoice',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'id' => 'tax_identification_number'
                            ],
                            'validation' => [
                                'required-entry' => true,
                            ]
                        ],
                        'company' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'label' => __('Company Name'),
                            'sortOrder' => 10,
                            'dataScope' => 'fullTaxInvoice.company',
                            'provider' => 'checkoutProvider',
                            'value' => $companyName,
                            'visible' => false,
                            'disabled' => true,
                            'config' => [
                                'customScope' => 'fullTaxInvoice',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                            ],
                            'validation' => [
                                'required-entry' => true,
                            ],
                        ],
                        'personal_firstname' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'label' => __('First Name'),
                            'sortOrder' => 10,
                            'dataScope' => 'fullTaxInvoice.personal_firstname',
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'disabled' => true,
                            'config' => [
                                'customScope' => 'fullTaxInvoice',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                            ],
                            'validation' => [
                                'required-entry' => true,
                            ],
                        ],
                        'personal_lastname' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'label' => __('Last Name'),
                            'sortOrder' => 15,
                            'dataScope' => 'fullTaxInvoice.personal_lastname',
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'disabled' => true,
                            'config' => [
                                'customScope' => 'fullTaxInvoice',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                            ],
                            'validation' => [
                                'required-entry' => true,
                            ],
                        ],
                        'company_branch' => [
                            'component' => 'Magento_Ui/js/form/element/boolean',
                            'label' => __(''),
                            'sortOrder' => 20,
                            'dataScope' => 'fullTaxInvoice.company_branch',
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'disabled' => true,
                            'config' => [
                                'customScope' => 'fullTaxInvoice',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'Tigren_CustomAddress/full-tax-invoice/branch',
                                'additionalClasses' => 'tax-invoice-label-options'
                            ],
                            'validation' => [
                                'required-entry' => true,
                            ],
                        ],
                        'telephone' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'label' => __('Phone Number'),
                            'config' => [
                                'customScope' => 'fullTaxInvoice',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'fullTaxInvoice.telephone',
                            'provider' => 'checkoutProvider',
                            'additionalClasses' => 'field',
                            'validation' => [
                                'required-entry' => true,
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param $jsLayoutResult
     * @throws LocalizedException
     */
    protected function _processBillingAddressComponentsInShipping(&$jsLayoutResult)
    {
        $paymentCode = 'shared';

        $addressElements = $this->getAddressAttributes();
        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['billing-step']['children']['billingAddress'] = [
            'config' => [
                'popUpForm' => [
                    'element' => '#opc-new-billing-address-' . $paymentCode,
                    'options' => [
                        'type' => 'popup',
                        'responsive' => true,
                        'innerScroll' => true,
                        'title' => __('Billing Address'),
                        'trigger' => 'opc-new-billing-address-' . $paymentCode,
                        'buttons' => [
                            'save' => [
                                'text' => __('Save'),
                                'class' => 'action primary action-save-address'
                            ],
                            'cancel' => [
                                'text' => __('Cancel'),
                                'class' => 'action secondary action-hide-popup'
                            ]
                        ]
                    ]
                ]
            ],
            'component' => 'Tigren_CustomAddress/js/view/billing',
            'displayArea' => 'billing-address',
            'provider' => 'checkoutProvider',
            'dataScopePrefix' => 'billingAddress' . $paymentCode,
            'paymentMethodCode' => 'shared',
            'children' => [
                'before-form' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'billing-before-form',
                    'children' => []
                ],
                'before-fields' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'billing-before-fields',
                    'children' => []
                ],
                'address-list' => [
                    'component' => 'Tigren_CustomAddress/js/view/billing-address/list',
                    'config' => [
                        'template' => 'Tigren_CustomAddress/billing-address/list'
                    ],
                    'displayArea' => 'billing-address-list'
                ],
                'address-fieldset' => $this->_processBillingAddressFieldsetComponents($addressElements)
            ]
        ];
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
     * Prepare shipping address field for shipping step for physical product
     *
     * @param $elements
     * @return array
     */
    public function getShippingAddressComponent($elements)
    {
        $providerName = 'checkoutProvider';
        $suggestionType = $this->customAddressHelper->getSuggestionType();

        if ($suggestionType == SuggestionType::SUGGESTION_TYPE_DROP_DOWN) {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'additional-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'shippingAddress',
                    [
                        'firstname' => [
                            'visible' => true,
                        ],
                        'lastname' => [
                            'visible' => true,
                        ],
                        'country_id' => [
                            'sortOrder' => 200
                        ],
                        'region' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'shippingAddress.region',
                            'label' => __('State/Region'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'sortOrder' => 210
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/region',
                            'config' => [
                                'customEntry' => 'shippingAddress.region',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'shipping-region'
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
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'shippingAddress.city',
                            'label' => __('City'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 230
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/city',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
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
                            'sortOrder' => 240
                        ],
                        'subdistrict' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('Subdistrict'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 250
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/subdistrict',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
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
                            'sortOrder' => 260
                        ],
                        'postcode' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/post-code',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'additionalClasses' => 'shipping-postcode'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'sortOrder' => 270
                        ],
                        'company' => [
                            'visible' => true,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 60
                        ],
                        'fax' => [
                            'visible' => false,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 290
                        ],
                        'telephone' => [
                            'visible' => 1,
                            'config' => [
                                'tooltip' => [
                                    'description' => __('For delivery questions.'),
                                ]
                            ],
                            'validation' => [
                                'validate-phone-number' => true
                            ],
                            'label' => __('Telephone'),
                            'sortOrder' => 60
                        ],
                        'type' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('Type'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'value' => 'new-shipping-address',
                            'sortOrder' => 300
                        ]
                    ]
                )
            ];
        } else {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'additional-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'shippingAddress',
                    [
                        'firstname' => [
                            'visible' => true,
                        ],
                        'lastname' => [
                            'visible' => true,
                        ],
                        'country_id' => [
                            'sortOrder' => 200
                        ],
                        'region' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'shippingAddress.region',
                            'label' => __('State/Region'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'sortOrder' => 201
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/region',
                            'config' => [
                                'customEntry' => 'shippingAddress.region',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'additionalClasses' => 'shipping-region'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                'field' => 'country_id'
                            ],
                            'sortOrder' => 202
                        ],
                        'subdistrict' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('City'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 210
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/subdistrict',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'additionalClasses' => 'shipping-subdistrict'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.city_id',
                                'field' => 'city_id'
                            ],
                            'sortOrder' => 220
                        ],
                        'city' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'shippingAddress.city',
                            'label' => __('City'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 230
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/city',
                            'config' => [
                                'customScope' => 'shippingAddress.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'customEntry' => 'shippingAddress.city',
                                'additionalClasses' => 'shipping-city'
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
                        'postcode' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/post-code',
                            'config' => [
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'additionalClasses' => 'shipping-postcode'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'sortOrder' => 270
                        ],
                        'company' => [
                            'visible' => 0,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 70
                        ],
                        'fax' => [
                            'visible' => false,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 290
                        ],
                        'telephone' => [
                            'visible' => 1,
                            'config' => [
                                'tooltip' => [
                                    'description' => __('For delivery questions.'),
                                ]
                            ],
                            'validation' => [
                                'validate-phone-number' => true
                            ],
                            'label' => __('Telephone'),
                            'sortOrder' => 60
                        ],
                        'type' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'shippingAddress',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('Type'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'value' => 'new-shipping-address',
                            'sortOrder' => 300
                        ]
                    ]
                )
            ];
        }

        if (isset($components['children'])) {
            foreach ($components['children'] as $key => $value) {
                $components['children'][$key]['config']['additionalClasses'] = 'shipping-' . $key;

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children'][$key]['options']);
                    $components['children'][$key]['deps'] = [$providerName];
                    $components['children'][$key]['imports'] = [
                        'initialOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key,
                        'setOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key
                    ];
                }

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children'][$key]['options']);
                    $components['children'][$key]['deps'] = [$providerName];
                }

                if ($key === 'city_id' || $key === 'subdistrict' || $key === 'subdistrict_id') {
                    $components['children'][$key]['dataScope'] = 'shippingAddress.custom_attributes.' . $key;
                }
            }
        }

        return $components;
    }

    /**
     * Prepare billing address field for shipping step for physical product
     *
     * @param $elements
     * @return array
     */
    public function _processBillingAddressFieldsetComponents($elements)
    {
        $paymentCode = 'shared';
        $providerName = 'checkoutProvider';
        $suggestionType = $this->customAddressHelper->getSuggestionType();

        if ($suggestionType == SuggestionType::SUGGESTION_TYPE_DROP_DOWN) {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'additional-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'billingAddress' . $paymentCode,
                    [
                        'firstname' => [
                            'visible' => true,
                        ],
                        'lastname' => [
                            'visible' => true,
                            'sortOrder' => 10
                        ],
                        'country_id' => [
                            'sortOrder' => 200
                        ],
                        'region' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'billingAddress' . $paymentCode . '.region',
                            'label' => __('State/Region'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'sortOrder' => 250
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/region',
                            'config' => [
                                'customEntry' => 'billingAddress' . $paymentCode . '.region',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'billing-region'
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
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'billingAddress' . $paymentCode . '.city',
                            'label' => __('City'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 230
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/city',
                            'config' => [
                                'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'billing-city'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:billingAddress' . $paymentCode . '.region_id',
                                'field' => 'region_id'
                            ],
                            'sortOrder' => 240
                        ],
                        'subdistrict' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('Subdistrict'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 250
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/subdistrict',
                            'config' => [
                                'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/select',
                                'additionalClasses' => 'billing-subdistrict'
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
                                'additionalClasses' => 'billing-postcode'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'sortOrder' => 270
                        ],
                        'company' => [
                            'visible' => 0,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 70
                        ],
                        'fax' => [
                            'visible' => false,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 290
                        ],
                        'telephone' => [
                            'visible' => 1,
                            'config' => [
                                'tooltip' => [
                                    'description' => __('For delivery questions.'),
                                ]
                            ],
                            'validation' => [
                                'validate-phone-number' => true
                            ],
                            'label' => __('Telephone'),
                            'sortOrder' => 60
                        ],
                        'type' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('Type'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'value' => 'new-billing-address',
                            'sortOrder' => 300
                        ]
                    ]
                )
            ];
        } else {
            $components = [
                'component' => 'uiComponent',
                'displayArea' => 'additional-fieldsets',
                'children' => $this->merger->merge(
                    $elements,
                    $providerName,
                    'billingAddress' . $paymentCode,
                    [
                        'firstname' => [
                            'visible' => true,
                        ],
                        'lastname' => [
                            'visible' => true,
                        ],
                        'country_id' => [
                            'sortOrder' => 200
                        ],
                        'region' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'billingAddress' . $paymentCode . '.region',
                            'label' => __('State/Region'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'sortOrder' => 250
                        ],
                        'region_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/region',
                            'config' => [
                                'customEntry' => 'billingAddress' . $paymentCode . '.region',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'additionalClasses' => 'billing-region'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                'field' => 'country_id'
                            ],
                            'sortOrder' => 260
                        ],
                        'subdistrict' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('Subdistrict'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 210
                        ],
                        'subdistrict_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/subdistrict',
                            'config' => [
                                'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'additionalClasses' => 'billing-subdistrict'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }.city_id',
                                'field' => 'city_id'
                            ],
                            'sortOrder' => 220
                        ],
                        'city' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'dataScope' => 'billingAddress' . $paymentCode . '.city',
                            'label' => __('City'),
                            'provider' => 'checkoutProvider',
                            'validation' => [
                                'required-entry' => 1,
                                'max_text_length' => 255,
                                'min_text_length' => 1
                            ],
                            'visible' => false,
                            'sortOrder' => 230
                        ],
                        'city_id' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/city',
                            'config' => [
                                'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'additionalClasses' => 'billing-city'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'filterBy' => [
                                'target' => '${ $.provider }:${ $.parentScope }' . $paymentCode . '.region_id',
                                'field' => 'region_id'
                            ],
                            'sortOrder' => 240
                        ],
                        'postcode' => [
                            'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/post-code',
                            'config' => [
                                'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                'additionalClasses' => 'billing-postcode'
                            ],
                            'validation' => [
                                'required-entry' => true
                            ],
                            'sortOrder' => 270
                        ],
                        'company' => [
                            'visible' => 0,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 70
                        ],
                        'fax' => [
                            'visible' => false,
                            'validation' => [
                                'min_text_length' => 0
                            ],
                            'sortOrder' => 290
                        ],
                        'telephone' => [
                            'visible' => 1,
                            'config' => [
                                'tooltip' => [
                                    'description' => __('For delivery questions.'),
                                ]
                            ],
                            'validation' => [
                                'validate-phone-number' => true
                            ],
                            'label' => __('Telephone'),
                            'sortOrder' => 60
                        ],
                        'type' => [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input'
                            ],
                            'label' => __('Type'),
                            'provider' => 'checkoutProvider',
                            'visible' => false,
                            'value' => 'new-billing-address',
                            'sortOrder' => 300
                        ]
                    ]
                )
            ];
        }

        if (isset($components['children'])) {
            foreach ($components['children'] as $key => $value) {
                $components['children'][$key]['config']['additionalClasses'] = 'billing-' . $key;

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children'][$key]['options']);
                    $components['children'][$key]['deps'] = [$providerName];
                    $components['children'][$key]['imports'] = [
                        'initialOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key,
                        'setOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key
                    ];
                }

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children'][$key]['options']);
                    $components['children'][$key]['deps'] = [$providerName];
                }

                if ($key === 'city_id' || $key === 'subdistrict' || $key === 'subdistrict_id') {
                    $components['children'][$key]['dataScope'] = 'billingAddress' . $paymentCode . '.custom_attributes.' . $key;
                }
            }
        }

        return $components;
    }

    /**
     * Appends billing address form component to payment layout
     * @param array $paymentLayout
     * @param array $elements
     * @return array
     */
    private function processPaymentChildrenComponents(array $paymentLayout, array $elements)
    {
        if (!isset($paymentLayout['payments-list']['children'])) {
            $paymentLayout['payments-list']['children'] = [];
        }

        if (!isset($paymentLayout['afterMethods']['children'])) {
            $paymentLayout['afterMethods']['children'] = [];
        }

        // The if billing address should be displayed on Payment method or page
        if ($this->getCheckoutDataHelper()->isDisplayBillingOnPaymentMethodAvailable()) {
            if (!empty($paymentLayout['payments-list']['children'])) {
                foreach (array_keys($paymentLayout['payments-list']['children']) as $key) {
                    if ($key == 'before-place-order') {
                        continue;
                    }

                    $paymentCode = substr($key, 0, -5);
                    if (isset($paymentLayout['payments-list']['children'][$key])) {
                        $paymentLayout['payments-list']['children'][$key] = $this->_processBillingAddressComponentsInPayment(
                            $paymentCode,
                            $elements
                        );
                    }
                }
            }
        } else {
            if (isset($paymentLayout['afterMethods']['children']['billing-address-form'])) {
                unset($paymentLayout['afterMethods']['children']['billing-address-form']);
            }
            $component['billing-address-form'] = $this->_processBillingAddressComponentsInPayment('shared', $elements);
            $paymentLayout['beforeMethods']['children'] =
                array_merge_recursive(
                    $component,
                    $paymentLayout['beforeMethods']['children']
                );
        }

        return $paymentLayout;
    }

    /**
     * Get checkout data helper instance
     *
     * @return Data
     * @deprecated 100.1.4
     */
    private function getCheckoutDataHelper()
    {
        if (!$this->checkoutDataHelper) {
            $this->checkoutDataHelper = ObjectManager::getInstance()->get(Data::class);
        }

        return $this->checkoutDataHelper;
    }

    /**
     * @param $paymentCode
     * @param $elements
     * @return array
     */
    private function _processBillingAddressComponentsInPayment($paymentCode, $elements)
    {
        $providerName = 'checkoutProvider';
        $suggestionType = $this->customAddressHelper->getSuggestionType();

        if ($suggestionType == SuggestionType::SUGGESTION_TYPE_DROP_DOWN) {
            $components = [
                'config' => [
                    'popUpForm' => [
                        'element' => '#opc-new-billing-address-' . $paymentCode,
                        'options' => [
                            'type' => 'popup',
                            'responsive' => true,
                            'innerScroll' => true,
                            'title' => __('Billing Address'),
                            'trigger' => 'opc-new-billing-address-' . $paymentCode,
                            'buttons' => [
                                'save' => [
                                    'text' => __('Save'),
                                    'class' => 'action primary action-save-address'
                                ],
                                'cancel' => [
                                    'text' => __('Cancel'),
                                    'class' => 'action secondary action-hide-popup'
                                ]
                            ]
                        ]
                    ]
                ],
                'component' => 'Tigren_CustomAddress/js/view/billing',
                'displayArea' => 'billing-address-form-' . $paymentCode,
                'provider' => 'checkoutProvider',
                'deps' => 'checkoutProvider',
                'dataScopePrefix' => 'billingAddress' . $paymentCode,
                'paymentMethodCode' => $paymentCode,
                'sortOrder' => 1,
                'children' => [
                    'before-form' => [
                        'component' => 'uiComponent',
                        'displayArea' => 'billing-before-form',
                        'children' => []
                    ],
                    'before-fields' => [
                        'component' => 'uiComponent',
                        'displayArea' => 'billing-before-fields',
                        'children' => []
                    ],
                    'address-list' => [
                        'component' => 'Tigren_CustomAddress/js/view/billing-address/list',
                        'config' => [
                            'template' => 'Tigren_CustomAddress/billing-address/list'
                        ],
                        'displayArea' => 'billing-address-list'
                    ],
                    'form-fields' => [
                        'component' => 'uiComponent',
                        'displayArea' => 'additional-fieldsets',
                        'children' => $this->merger->merge(
                            $elements,
                            'checkoutProvider',
                            'billingAddress' . $paymentCode,
                            [
                                'firstname' => [
                                    'visible' => true,
                                ],
                                'lastname' => [
                                    'visible' => true,
                                    'sortOrder' => 10
                                ],
                                'country_id' => [
                                    'sortOrder' => 200
                                ],
                                'region' => [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'dataScope' => 'billingAddress' . $paymentCode . '.region',
                                    'label' => __('State/Region'),
                                    'provider' => 'checkoutProvider',
                                    'visible' => false,
                                    'sortOrder' => 250
                                ],
                                'region_id' => [
                                    'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/region',
                                    'config' => [
                                        'customEntry' => 'billingAddress' . $paymentCode . '.region',
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/select',
                                        'additionalClasses' => 'billing-region'
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
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'dataScope' => 'billingAddress' . $paymentCode . '.city',
                                    'label' => __('City'),
                                    'provider' => 'checkoutProvider',
                                    'validation' => [
                                        'required-entry' => 1,
                                        'max_text_length' => 255,
                                        'min_text_length' => 1
                                    ],
                                    'visible' => false,
                                    'sortOrder' => 230
                                ],
                                'city_id' => [
                                    'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/city',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/select',
                                        'additionalClasses' => 'billing-city'
                                    ],
                                    'validation' => [
                                        'required-entry' => true
                                    ],
                                    'filterBy' => [
                                        'target' => '${ $.provider }:billingAddress' . $paymentCode . '.region_id',
                                        'field' => 'region_id'
                                    ],
                                    'sortOrder' => 240
                                ],
                                'subdistrict' => [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'label' => __('Subdistrict'),
                                    'provider' => 'checkoutProvider',
                                    'validation' => [
                                        'required-entry' => 1,
                                        'max_text_length' => 255,
                                        'min_text_length' => 1
                                    ],
                                    'visible' => false,
                                    'sortOrder' => 250
                                ],
                                'subdistrict_id' => [
                                    'component' => 'Tigren_CustomAddress/js/form/element/drop-down/directory/subdistrict',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/select',
                                        'additionalClasses' => 'billing-subdistrict'
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
                                        'additionalClasses' => 'billing-postcode'
                                    ],
                                    'validation' => [
                                        'required-entry' => true
                                    ],
                                    'sortOrder' => 270
                                ],
                                'company' => [
                                    'visible' => 0,
                                    'validation' => [
                                        'min_text_length' => 0
                                    ],
                                    'sortOrder' => 70
                                ],
                                'fax' => [
                                    'visible' => false,
                                    'validation' => [
                                        'min_text_length' => 0
                                    ],
                                    'sortOrder' => 290
                                ],
                                'telephone' => [
                                    'visible' => 1,
                                    'config' => [
                                        'tooltip' => [
                                            'description' => __('For delivery questions.'),
                                        ]
                                    ],
                                    'validation' => [
                                        'validate-phone-number' => true
                                    ],
                                    'label' => __('Telephone'),
                                    'sortOrder' => 60
                                ],
                                'type' => [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'label' => __('Type'),
                                    'provider' => 'checkoutProvider',
                                    'visible' => false,
                                    'value' => 'new-billing-address',
                                    'sortOrder' => 300
                                ]
                            ]
                        ),
                    ],
                ],
            ];
        } else {
            $components = [
                'config' => [
                    'popUpForm' => [
                        'element' => '#opc-new-billing-address-' . $paymentCode,
                        'options' => [
                            'type' => 'popup',
                            'responsive' => true,
                            'innerScroll' => true,
                            'title' => __('Billing Address'),
                            'trigger' => 'opc-new-billing-address-' . $paymentCode,
                            'buttons' => [
                                'save' => [
                                    'text' => __('Save'),
                                    'class' => 'action primary action-save-address'
                                ],
                                'cancel' => [
                                    'text' => __('Cancel'),
                                    'class' => 'action secondary action-hide-popup'
                                ]
                            ]
                        ]
                    ]
                ],
                'component' => 'Tigren_CustomAddress/js/view/billing',
                'displayArea' => 'billing-address-form-' . $paymentCode,
                'provider' => 'checkoutProvider',
                'deps' => 'checkoutProvider',
                'dataScopePrefix' => 'billingAddress' . $paymentCode,
                'paymentMethodCode' => $paymentCode,
                'sortOrder' => 1,
                'children' => [
                    'before-form' => [
                        'component' => 'uiComponent',
                        'displayArea' => 'billing-before-form',
                        'children' => []
                    ],
                    'before-fields' => [
                        'component' => 'uiComponent',
                        'displayArea' => 'billing-before-fields',
                        'children' => []
                    ],
                    'address-list' => [
                        'component' => 'Tigren_CustomAddress/js/view/billing-address/list',
                        'config' => [
                            'template' => 'Tigren_CustomAddress/billing-address/list'
                        ],
                        'displayArea' => 'billing-address-list'
                    ],
                    'form-fields' => [
                        'component' => 'uiComponent',
                        'displayArea' => 'additional-fieldsets',
                        'children' => $this->merger->merge(
                            $elements,
                            'checkoutProvider',
                            'billingAddress' . $paymentCode,
                            [
                                'firstname' => [
                                    'visible' => true,
                                ],
                                'lastname' => [
                                    'visible' => true,
                                ],
                                'country_id' => [
                                    'sortOrder' => 200
                                ],
                                'region' => [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'dataScope' => 'billingAddress' . $paymentCode . '.region',
                                    'label' => __('State/Region'),
                                    'provider' => 'checkoutProvider',
                                    'visible' => false,
                                    'sortOrder' => 250
                                ],
                                'region_id' => [
                                    'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/region',
                                    'config' => [
                                        'customEntry' => 'billingAddress' . $paymentCode . '.region',
                                        'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                        'additionalClasses' => 'billing-region'
                                    ],
                                    'validation' => [
                                        'required-entry' => true
                                    ],
                                    'filterBy' => [
                                        'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                        'field' => 'country_id'
                                    ],
                                    'sortOrder' => 260
                                ],
                                'subdistrict' => [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'label' => __('Subdistrict'),
                                    'provider' => 'checkoutProvider',
                                    'validation' => [
                                        'required-entry' => 1,
                                        'max_text_length' => 255,
                                        'min_text_length' => 1
                                    ],
                                    'visible' => false,
                                    'sortOrder' => 210
                                ],
                                'subdistrict_id' => [
                                    'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/subdistrict',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                        'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                        'additionalClasses' => 'billing-subdistrict'
                                    ],
                                    'validation' => [
                                        'required-entry' => true
                                    ],
                                    'filterBy' => [
                                        'target' => '${ $.provider }:${ $.parentScope }.city_id',
                                        'field' => 'city_id'
                                    ],
                                    'sortOrder' => 220
                                ],
                                'city' => [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'dataScope' => 'billingAddress' . $paymentCode . '.city',
                                    'label' => __('City'),
                                    'provider' => 'checkoutProvider',
                                    'validation' => [
                                        'required-entry' => 1,
                                        'max_text_length' => 255,
                                        'min_text_length' => 1
                                    ],
                                    'visible' => false,
                                    'sortOrder' => 230
                                ],
                                'city_id' => [
                                    'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/city',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $paymentCode . '.custom_attributes',
                                        'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                        'additionalClasses' => 'billing-city'
                                    ],
                                    'validation' => [
                                        'required-entry' => true
                                    ],
                                    'filterBy' => [
                                        'target' => '${ $.provider }:${ $.parentScope }' . $paymentCode . '.region_id',
                                        'field' => 'region_id'
                                    ],
                                    'sortOrder' => 240
                                ],
                                'postcode' => [
                                    'component' => 'Tigren_CustomAddress/js/form/element/auto-complete/directory/post-code',
                                    'config' => [
                                        'template' => 'Tigren_CustomAddress/form/element/auto-complete/directory/ui-select',
                                        'additionalClasses' => 'billing-postcode'
                                    ],
                                    'validation' => [
                                        'required-entry' => true
                                    ],
                                    'sortOrder' => 270
                                ],
                                'company' => [
                                    'visible' => 0,
                                    'validation' => [
                                        'min_text_length' => 0
                                    ],
                                    'sortOrder' => 70
                                ],
                                'fax' => [
                                    'visible' => false,
                                    'validation' => [
                                        'min_text_length' => 0
                                    ],
                                    'sortOrder' => 290
                                ],
                                'telephone' => [
                                    'visible' => 1,
                                    'config' => [
                                        'tooltip' => [
                                            'description' => __('For delivery questions.'),
                                        ]
                                    ],
                                    'validation' => [
                                        'validate-phone-number' => true
                                    ],
                                    'label' => __('Telephone'),
                                    'sortOrder' => 60
                                ],
                                'type' => [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input'
                                    ],
                                    'label' => __('Type'),
                                    'provider' => 'checkoutProvider',
                                    'visible' => false,
                                    'value' => 'new-billing-address',
                                    'sortOrder' => 300
                                ]
                            ]
                        ),
                    ],
                ],
            ];
        }

        if (isset($components['children']['form-fields']['children'])) {
            foreach ($components['children']['form-fields']['children'] as $key => $value) {
                $components['children']['form-fields']['children'][$key]['config']['additionalClasses'] = 'billing-' . $key;

                if ($key === 'city_id' || $key === 'subdistrict_id' || $key === 'postcode') {
                    unset($components['children']['form-fields']['children'][$key]['options']);
                    $components['children']['form-fields']['children'][$key]['deps'] = [$providerName];
                    $components['children']['form-fields']['children'][$key]['imports'] = [
                        'initialOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key,
                        'setOptions' => 'index = ' . $providerName . ':additional_dictionaries.' . $key
                    ];
                }

                if ($key === 'city_id' || $key === 'subdistrict' || $key === 'subdistrict_id') {
                    $components['children']['form-fields']['children'][$key]['dataScope'] = 'billingAddress' . $paymentCode . '.custom_attributes.' . $key;
                }

                if ($key === 'postcode') {
                    $components['children']['form-fields']['children'][$key]['config']['suggestType'] = $suggestionType;
                }
            }
        }

        return $components;
    }
}
