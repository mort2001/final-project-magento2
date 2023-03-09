<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Customer\Block\Address\Renderer;

use Closure;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Metadata\ElementFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\ScopeInterface;
use Tigren\CustomAddress\Helper\Data;

/**
 * Class DefaultRenderer
 * @package Tigren\CustomAddress\Plugin\Magento\Customer\Block\Address\Renderer
 */
class DefaultRenderer extends \Magento\Customer\Block\Address\Renderer\DefaultRenderer
{
    /**
     * @var Data
     */
    protected $customAddressHelper;

    /**
     * @var State
     */
    protected $_state;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * DefaultRenderer constructor.
     * @param Context $context
     * @param ElementFactory $elementFactory
     * @param CountryFactory $countryFactory
     * @param AddressMetadataInterface $metadataService
     * @param Mapper $addressMapper
     * @param Data $customerAddressHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ElementFactory $elementFactory,
        CountryFactory $countryFactory,
        AddressMetadataInterface $metadataService,
        Mapper $addressMapper,
        Data $customerAddressHelper,
        State $state,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $elementFactory,
            $countryFactory,
            $metadataService,
            $addressMapper,
            $data
        );
        $this->_registry = $registry;
        $this->customAddressHelper = $customerAddressHelper;
        $this->_state = $state;
    }

    /**
     * @param \Magento\Customer\Block\Address\Renderer\DefaultRenderer $subject
     * @param Closure $proceed
     * @param $addressAttributes
     * @param null $format
     * @return string
     * @throws LocalizedException
     */
    public function aroundRenderArray(
        \Magento\Customer\Block\Address\Renderer\DefaultRenderer $subject,
        Closure $proceed,
        $addressAttributes,
        $format = null
    ) {
        switch ($subject->getType()->getCode()) {
            case 'html':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_HTML;
                break;
            case 'pdf':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_PDF;
                break;
            case 'oneline':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_ONELINE;
                break;
            default:
                $dataFormat = ElementFactory::OUTPUT_FORMAT_TEXT;
                break;
        }

        $locale = $this->getOrderLocale();

        $attributesMetadata = $this->_addressMetadataService->getAllAttributesMetadata();
        $data = [];
        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                continue;
            }

            $attributeCode = $attributeMetadata->getAttributeCode();
            if ($attributeCode == 'country_id' && isset($addressAttributes['country_id'])) {
                $data['country'] = $this->customAddressHelper->getCountryById(
                    $addressAttributes['country_id'],
                    $locale
                );
            } elseif ($attributeCode == 'region_id' && isset($addressAttributes['region_id'])) {
                $data['region'] = $this->customAddressHelper->getRegionById($addressAttributes['region_id'], $locale);
            } elseif ($attributeCode == 'city_id' && isset($addressAttributes['city_id'])) {
                $data['city'] = $this->customAddressHelper->getCityById($addressAttributes['city_id'], $locale);
            } elseif ($attributeCode == 'subdistrict_id' && isset($addressAttributes['subdistrict_id'])) {
                $data['subdistrict'] = $this->customAddressHelper->getSubdistrictById(
                    $addressAttributes['subdistrict_id'],
                    $locale
                );
            } elseif (isset($addressAttributes[$attributeCode])) {
                $value = $addressAttributes[$attributeCode];
                $dataModel = $this->_elementFactory->create($attributeMetadata, $value, 'customer_address');
                $value = $dataModel->outputValue($dataFormat);
                if ($attributeMetadata->getFrontendInput() == 'multiline') {
                    $values = $dataModel->outputValue(ElementFactory::OUTPUT_FORMAT_ARRAY);
                    // explode lines
                    foreach ($values as $k => $v) {
                        $key = sprintf('%s%d', $attributeCode, $k + 1);
                        $data[$key] = $v;
                    }
                }
                $data[$attributeCode] = $value;
            }
        }
        if ($subject->getType()->getEscapeHtml()) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->escapeHtml($value);
            }
        }
        $format = $format !== null ? $format : $subject->getFormatArray($addressAttributes);
        $template = $this->filterManager->template($format, ['variables' => $data]);

        if (!empty($addressAttributes['is_full_invoice'])) {
            if ($addressAttributes['is_full_invoice']) {
                $template .= '<br/><strong>' . __('Full Tax Invoice') . '</strong>';
                $template .= '<br/>' . __('Tax ID number: ' . $addressAttributes['tax_identification_number']);
                if ($addressAttributes['invoice_type'] === 'corporate') {
                    if (!$addressAttributes['head_office']) {
                        if ($addressAttributes['branch_office']) {
                            $template .= '<br/>' . __('Company Name: ' . $addressAttributes['company']);
                            $template .= '<br/>' . __('Branch ID: ' . $addressAttributes['branch_office']);
                        }
                    } else {
                        $template .= '<br/>' . __('Company Name: ' . $addressAttributes['company']);
                        $template .= '<br/>' . __('Head office');
                    }
                }
            }
        }

        return $template;
    }

    /**
     * @return mixed|string
     */
    public function getOrderLocale()
    {
        $locale = '';
        $currentOrder = $this->_registry->registry('current_order');

        if ($currentOrder && $currentOrder->getId()) {
            $locale = $this->_scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $currentOrder->getStoreId()
            );
        }

        return $locale;
    }
}
