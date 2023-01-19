<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Block;

use Magento\Checkout\Model\Cart;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class CartInfo
 *
 * @package Tigren\Ajaxcart\Block
 */
class CartInfo extends Template
{
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * CartInfo constructor.
     *
     * @param Context $context
     * @param Cart    $cart
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Cart $cart,
        array $data
    ) {
        parent::__construct($context, $data);
        $this->_cart = $cart;
    }

    /**
     * @return int
     */
    public function getItemsCount()
    {
        return $this->_cart->getItemsCount();
    }

    /**
     * @return float|int
     */
    public function getItemsQty()
    {
        return $this->_cart->getItemsQty();
    }

    /**
     * @return float
     */
    public function getSubTotal()
    {
        return $this->_cart->getQuote()->getSubtotal();
    }
}
