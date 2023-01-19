<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Block;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Ajaxlogin\Helper\Data;

/**
 * Ajaxsuite js block
 */
class Js extends Template
{
    /**
     * @var string
     */
    protected $_template = 'js/main.phtml';

    /**
     * Ajaxsuite helper
     */
    protected $_ajaxLoginHelper;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @param Context $context
     * @param FormKey $formKey
     * @param Data    $ajaxLoginHelper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Data $ajaxLoginHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
        $this->_ajaxLoginHelper = $ajaxLoginHelper;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAjaxLoginInitOptions()
    {
        return $this->_ajaxLoginHelper->getAjaxLoginInitOptions();
    }
}
