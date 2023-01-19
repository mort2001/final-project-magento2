<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Block\Adminhtml\Customer\Edit\Renderer;

use Magento\Backend\Block\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Model\AdminOrder\Create;
use Tigren\CustomAddress\Helper\Data;

/**
 * Customer address region field renderer
 */
class CountryId extends \Magento\Backend\Block\AbstractBlock implements RendererInterface
{
    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @var RendererInterface
     */
    protected $_renderer;

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
        $this->_renderer = Form::getElementRenderer();
        parent::__construct($context, $data);
    }

    /**
     * Output the region element and javascript that makes it dependent from country element
     *
     * @param AbstractElement $element
     *
     * @return string
     *
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function render(AbstractElement $element)
    {
        $objectManager = ObjectManager::getInstance();
        $orderCreate = $objectManager->get(Create::class);
        $_helper = $objectManager->get(Data::class);

        $regionId = $element->getForm()->getElement('region_id')->getValue();
        $defaultRegion = $orderCreate->getBillingAddress()->getRegionId() ? $orderCreate->getBillingAddress()->getRegionId() : $regionId;

        $prefix = '#' . $element->getForm()->getHtmlIdPrefix();
        $parentId = mb_substr($element->getForm()->getHtmlIdPrefix(), 0, -1);

        $jsonData = '{"regionUpdater": {"optionalRegionAllowed": "true","regionListId":"' . $prefix . 'region_id","regionInputId":"' . $prefix . 'region","postcodeId":"' . $prefix . 'postcode","form" : "#edit_form","regionJson": ' . str_replace("'",
                "",
                $_helper->getRegionJson()) . ',"defaultRegion" : "' . $defaultRegion . '","type":"' . $parentId . '","countriesWithOptionalZip":' . $_helper->getCountriesWithOptionalZip(true) . '}}';

        $html = '<div class="field field-state required admin__field _required">';
        $element->addClass('select admin__control-select required-entry');
        $element->setRequired(true);
        $html .= $element->getLabelHtml() . '<div class="control admin__field-control">';

        if ($element->getBeforeElementHtml()) {
            $html .= '<label class="addbefore" for="' .
                $element->getHtmlId() .
                '">' .
                $element->getBeforeElementHtml() .
                '</label>';
        }

        $html .= '<select id="' . $element->getHtmlId()
            . '" name="' . $element->getName()
            . '" ' . $element->serialize($element->getHtmlAttributes()) . $this->_getUiId($element)
            . ' data-mage-init=\'' . $jsonData . '\' >' . "\n";

        $value = $element->getValue();
        if (!is_array($value)) {
            $value = [$value];
        }

        if ($values = $element->getValues()) {
            foreach ($values as $key => $option) {
                if (!is_array($option)) {
                    $html .= $this->_optionToHtml(['value' => $key, 'label' => $option], $value, $element);
                } elseif (is_array($option['value'])) {
                    $html .= '<optgroup label="' . $option['label'] . '">' . "\n";
                    foreach ($option['value'] as $groupItem) {
                        $html .= $this->_optionToHtml($groupItem, $value, $element);
                    }
                    $html .= '</optgroup>' . "\n";
                } else {
                    $html .= $this->_optionToHtml($option, $value, $element);
                }
            }
        }

        $html .= '</select>' . "\n";
        if ($element->getAfterElementHtml()) {
            $html .= '<label class="addafter" for="' .
                $element->getHtmlId() .
                '">' .
                "\n{$element->getAfterElementHtml()}\n" .
                '</label>' .
                "\n";
        }

        $html .= '</div></div>' . "\n";

        return $html;
    }

    /**
     * Get Ui Id.
     *
     * @param null|string $suffix
     *
     * @return string
     */
    protected function _getUiId($element, $suffix = null)
    {
        if ($this->_renderer instanceof AbstractBlock) {
            return $this->_renderer->getUiId($element->getType(), $element->getName(), $suffix);
        } else {
            return ' data-ui-id="form-element-' . $this->_escaper->escapeHtml($element->getName()) . ($suffix ?: '') . '"';
        }
    }

    /**
     * Format an option as Html
     *
     * @param array $option
     * @param array $selected
     *
     * @return string
     */
    protected function _optionToHtml($option, $selected, $element)
    {
        if (is_array($option['value'])) {
            $html = '<optgroup label="' . $option['label'] . '">' . "\n";
            foreach ($option['value'] as $groupItem) {
                $html .= $this->_optionToHtml($groupItem, $selected, $element);
            }
            $html .= '</optgroup>' . "\n";
        } else {
            $html = '<option value="' . $this->_escape($option['value']) . '"';
            $html .= isset($option['title']) ? 'title="' . $this->_escape($option['title']) . '"' : '';
            $html .= isset($option['style']) ? 'style="' . $option['style'] . '"' : '';
            if (in_array($option['value'], $selected)) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $this->_escape($option['label']) . '</option>' . "\n";
        }

        return $html;
    }

    /**
     * Escape a string's contents.
     *
     * @param string $string
     *
     * @return string
     */
    protected function _escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT);
    }
}
