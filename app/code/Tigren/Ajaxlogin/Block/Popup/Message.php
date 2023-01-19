<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Block\Popup;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Ajaxlogin\Helper\Data;

/**
 * Class Message
 *
 * @package Tigren\Ajaxlogin\Block\Popup
 */
class Message extends Template
{
    /**
     * @var Data
     */
    protected $_ajaxLoginHelper;

    /**
     * Message constructor.
     *
     * @param Context $context
     * @param Data    $ajaxLoginHelper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $ajaxLoginHelper,
        array $data = []
    ) {
        $this->_ajaxLoginHelper = $ajaxLoginHelper;
        parent::__construct($context, $data);
    }
}
