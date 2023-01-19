<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block\Adminhtml\Banner\Widget;

use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Ajaxcart js block
 */
class ChooserJs extends Template
{
    /**
     * @var string
     */
    protected $_template = 'js/chooser.phtml';

    /**
     * @var Factory
     */
    protected $_elementFactory;

    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     * Generate url to get products chooser by ajax query
     *
     * @return string
     */
    public function getBannersChooserUrl()
    {
        return $this->getUrl('bannersmanager/banners/banners', ['_current' => true]);
    }

    /**
     * @return array
     */
    public function getPage()
    {
        $element = $this->getElement();
        return [
            'group' => 'banners-chooser',
            'banners' => $element->getValue(),
        ];
    }

    /**
     * Return chooser HTML and init scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        $element = $this->getElement();
        $this->setElementValue($element->getValue());

        $hidden = $this->_elementFactory->create('hidden', ['data' => $element->getData()]);
        $hidden->setId("mb-banners-selected")->setForm($element->getForm());
        $hidden->setValue($element->getValue());

        $hiddenHtml = $hidden->getElementHtml();

        $html = parent::_toHtml();

        return $hiddenHtml . $html;
    }
}
