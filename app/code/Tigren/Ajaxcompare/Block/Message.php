<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcompare\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Ajaxsuite\Helper\Data;

/**
 * Class Message
 *
 * @package Tigren\Ajaxcompare\Block
 */
class Message extends Template
{
    /**
     * @var Data
     */
    protected $_ajaxsuiteHelper;

    /**
     * Message constructor.
     *
     * @param Context $context
     * @param Data    $ajaxsuiteHelper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $ajaxsuiteHelper,
        array $data
    ) {
        parent::__construct($context, $data);
        $this->_ajaxsuiteHelper = $ajaxsuiteHelper;
    }

    /**
     * @return mixed|string
     */
    public function getMessage()
    {
        $message = $this->_ajaxsuiteHelper->getScopeConfig('ajaxcompare/general/message');
        if (!$message) {
            $message = __('You added this product to the comparison list');
        }
        return $message;
    }
}
