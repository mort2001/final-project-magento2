<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcompare\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Ajaxcompare\Helper\Data;

/**
 * Class Js
 *
 * @package Tigren\Ajaxcompare\Block
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
    protected $_ajaxCompareHelper;

    /**
     * Js constructor.
     *
     * @param Context $context
     * @param Data    $ajaxCompareHelper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $ajaxCompareHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_ajaxCompareHelper = $ajaxCompareHelper;
    }

    /**
     * @return string
     */
    public function getAjaxCompareInitOptions()
    {
        return $this->_ajaxCompareHelper->getAjaxCompareInitOptions();
    }
}
