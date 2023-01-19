<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Block\Adminhtml\Customer\Edit\Renderer;

use Magento\Backend\Block\AbstractBlock;
use Magento\Backend\Block\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\AdminOrder\Create;
use Tigren\CustomAddress\Helper\Data;

/**
 * Customer address region field renderer
 */
class Region extends AbstractBlock implements RendererInterface
{
    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @param Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        array $data = []
    ) {
        $this->_directoryHelper = $directoryHelper;
        parent::__construct($context, $data);
    }

    /**
     * Output the region element and javasctipt that makes it dependent from country element
     *
     * @param AbstractElement $element
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function render(AbstractElement $element)
    {
        $country = $element->getForm()->getElement('country_id');
        if (!$country) {
            return $element->getDefaultHtml();
        }

        $objectManager = ObjectManager::getInstance();
        $orderCreate = $objectManager->get(Create::class);
        $_helper = $objectManager->get(Data::class);

        $cityId = $element->getForm()->getElement('city_id')->getValue();

        $html = '<div class="field field-state required admin__field _required">';
        $element->setClass('input-text admin__control-text');
        $element->setRequired(true);
        $html .= $element->getLabelHtml() . '<div class="control admin__field-control">';
        $html .= $element->getElementHtml();

        $selectName = str_replace('region', 'region_id', $element->getName());
        $selectId = $element->getHtmlId() . '_id';

        $prefix = '#' . $element->getForm()->getHtmlIdPrefix();
        $parentId = mb_substr($element->getForm()->getHtmlIdPrefix(), 0, -1);

        /** @var Quote $quote */
        $quote = $objectManager->create('Magento\Quote\Model\Quote')->load($orderCreate->getQuote()->getId());
        $defaultCity = $quote->getBillingAddress()->getCityId() ? $quote->getBillingAddress()->getCityId() : $cityId;

        $jsonData = '{"cityUpdater": {"countryId": "' . $prefix . 'country_id","regionListId":"' . $prefix . 'region_id","cityListId":"' . $prefix . 'city_id","cityInputId":"' . $prefix . 'city","form" : "#edit_form","cityJson": ' . str_replace("'",
                "",
                $_helper->getCityJson()) . ',"type":"' . $parentId . '","defaultCity" : "' . $defaultCity . '"}}';

        $html .= '<select id="' .
            $selectId .
            '" name="' .
            $selectName .
            '" class="select required-entry admin__control-select" style="display:none" data-mage-init=\'' . $jsonData . '\'  >';
        $html .= '<option value="">' . __('Please select') . '</option>';
        $html .= '</select>';

        $html .= '<script>' . "\n";
        $html .= 'require(["prototype", "mage/adminhtml/form"], function(){';

        $html .= '});';
        $html .= '</script>' . "\n";

        $html .= '</div></div>' . "\n";

        return $html;
    }
}
