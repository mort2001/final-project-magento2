<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\AdvancedCheckout\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Cart;

/**
 * Class ClearCart
 * @package Tigren\AdvancedCheckout\Controller\Payment
 */
class ClearCart extends Action
{
    /**
     * @var CartRepositoryInterface
     */
    protected $_cartRepository;

    /**
     * @var Cart
     */
    protected $_modelCart;

    /**
     * @var Item
     */
    protected $modelCartItem;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param ResultFactory $resultFactory
     * @param CheckoutSession $checkoutSession
     * @param Item $modelCartItem
     * @param Cart $cart
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Context                 $context,
        SerializerInterface     $serializer,
        ResultFactory           $resultFactory,
        CheckoutSession         $checkoutSession,
        Item                    $modelCartItem, Cart $cart,
        CartRepositoryInterface $cartRepository
    )
    {
        $this->_cartRepository = $cartRepository;
        $this->_modelCart = $cart;
        $this->modelCartItem = $modelCartItem;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $resultFactory;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json&ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $status = $this->getRequest()->getParam('status');
        if ($status) {
            $cart = $this->checkoutSession
                ->clearQuote()
                ->getQuote()
                ->removeAllItems();
            $this->_cartRepository->save($cart);
            $this->checkoutSession->replaceQuote($cart);
            $this->messageManager->addSuccessMessage("Clear Cart Successfully!!!");
        }

        return $this->resultFactory
            ->create(ResultFactory::TYPE_JSON)
            ->setData([
                'status' => "ok",
            ]);
    }
}