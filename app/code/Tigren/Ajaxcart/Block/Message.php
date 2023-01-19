<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Ajaxcart\Helper\Data;

/**
 * Class Message
 *
 * @package Tigren\Ajaxcart\Block
 */
class Message extends Template
{
    /**
     * @var Data
     */
    protected $_ajaxcartHelper;

    /**
     * Message constructor.
     *
     * @param Context $context
     * @param Data    $ajaxcartHelper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $ajaxcartHelper,
        array $data
    ) {
        parent::__construct($context, $data);
        $this->_ajaxcartHelper = $ajaxcartHelper;
    }

    /**
     * @return mixed|string
     */
    public function getMessage()
    {
        $message = $this->_ajaxcartHelper->getScopeConfig('ajaxcart/general/message');
        if (!$message) {
            $message = 'You have recently added this product to your Cart';
        }
        return $message;
    }
}
