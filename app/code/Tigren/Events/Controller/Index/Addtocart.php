<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Index;

use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Addtocart
 *
 * @package Tigren\Events\Controller\Index
 */
class Addtocart extends Action
{
    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * Addtocart constructor.
     *
     * @param Context $context
     * @param ProductFactory $productFactory
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        Cart $cart
    ) {
        $this->_productFactory = $productFactory;
        $this->_cart = $cart;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product');
        $formKey = $this->getRequest()->getParam('formkey');
        $params = [
            'product' => $productId,
            'formkey' => $formKey,
            'qty' => 1
        ];
        $product = $this->_productFactory->create()->load($productId);
        $this->_cart->addProduct($product, $params);
        $this->_cart->save();

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }
}
