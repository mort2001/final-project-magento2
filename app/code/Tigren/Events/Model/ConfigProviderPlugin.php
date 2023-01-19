<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Item;

/**
 * Class ConfigProviderPlugin
 *
 * @package Tigren\Events\Model
 */
class ConfigProviderPlugin implements ConfigProviderInterface
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Cart $cart
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Cart $cart
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $result = [];

        $result['quoteData']['isEvent'] = $this->getIsEvents();
        return $result;
    }

    /**
     * @return bool
     */
    public function getIsEvents()
    {
        $isEvents = true;
        $countItems = 0;
        foreach ($this->cart->getQuote()->getItemsCollection() as $_item) {
            /* @var $_item Item */
            if ($_item->isDeleted() || $_item->getParentItemId()) {
                continue;
            }
            $countItems++;
            if ($_item->getProduct()->getTypeId() != 'event') {
                $isEvents = false;
                break;
            }
        }
        return $countItems == 0 ? false : $isEvents;
    }
}
