<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Block;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Ajaxcart\Helper\Data;

/**
 * Class Js
 *
 * @package Tigren\Ajaxcart\Block
 */
class Js extends Template
{
    /**
     * @var string
     */
    protected $_template = 'js/main.phtml';

    /**
     * @var Data
     */
    protected $_ajaxcartHelper;

    /**
     * Js constructor.
     *
     * @param Context $context
     * @param Data    $ajaxcartHelper
     * @param FormKey $formKey
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $ajaxcartHelper,
        FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_ajaxcartHelper = $ajaxcartHelper;
        $this->formKey = $formKey;
    }

    /**
     * @return string
     */
    public function getAjaxCartInitOptions()
    {
        return $this->_ajaxcartHelper->getAjaxCartInitOptions();
    }

    /**
     * @return string
     */
    public function getAjaxSidebarInitOptions()
    {
        $icon = $this->getViewFileUrl('images/loader-1.gif');
        return $this->_ajaxcartHelper->getAjaxSidebarInitOptions($icon);
    }
}
