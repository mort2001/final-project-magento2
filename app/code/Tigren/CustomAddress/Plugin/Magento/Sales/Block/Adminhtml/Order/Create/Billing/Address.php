<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin\Magento\Sales\Block\Adminhtml\Order\Create\Billing;

use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Address
 * @package Tigren\CustomAddress\Plugin\Magento\Sales\Block\Adminhtml\Order\Create\Billing
 */
class Address
{
    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Form\Address $subject
     * @param Form $result
     * @return Form
     */
    public function afterGetForm(
        \Magento\Sales\Block\Adminhtml\Order\Create\Form\Address $subject,
        Form $result
    ) {
        $hideAttributes = [
            'country_id',
            'region_id',
            'city',
            'postcode',
            'subdistrict',
            'vat_id',
            'region'
        ];

        $mainFieldset = $result->getElement('main');
        $mainFieldsetElements = $mainFieldset->getElements();
        if (count($mainFieldsetElements)) {
            /** @var AbstractElement $element * */
            foreach ($mainFieldsetElements as $element) {
                if (in_array($element->getId(), $hideAttributes)) {
                    if ($element->getId() != 'region' && $element->getId() != 'region_id') {
                        $js = '<style>
                            .field-' . $element->getId() . '{display: none}
                        </style>';
                    } else {
                        $js = '<style>
                            .field-state{display: none}
                        </style>';
                    }
                    $element->setData('after_element_html', $js);
                }
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Form\Address $subject
     * @param $result
     * @return string
     * @throws LocalizedException
     */
    public function afterToHtml(\Magento\Sales\Block\Adminhtml\Order\Create\Form\Address $subject, $result)
    {
        $addressForm = $subject->getLayout()->createBlock(
            'Tigren\CustomAddress\Block\Adminhtml\Sales\Order\Create\Billing\CustomAddress'
        );
        $result .= $addressForm->toHtml();
        return $result;
    }
}
