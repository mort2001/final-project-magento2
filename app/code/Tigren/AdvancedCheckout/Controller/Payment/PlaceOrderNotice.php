<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\AdvancedCheckout\Controller\Payment;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class PlaceOrderNotice
 * @package Tigren\AdvancedCheckout\Controller\Payment
 */
class PlaceOrderNotice extends Action
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @param Context $context
     * @param Session $session
     * @param CollectionFactory $orderCollectionFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context             $context,
        Session             $session,
        CollectionFactory   $orderCollectionFactory,
        SerializerInterface $serializer
    )
    {
        $this->serializer = $serializer;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_session = $session;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = [];
        $data = $this->getRequest()->getParam('data');
        if ($data) {
            $customerId = $this->_session->getCustomerId();
            $orderCollection = $this->_orderCollectionFactory->create();
            $orderCollection->addFieldToFilter('customer_id', $customerId);
            $orderCollection->setOrder('created_at', 'DESC');

            $lastOrder = $orderCollection->setPageSize(1)->getFirstItem();
            $status = $lastOrder->getStatus();

            if ($status == 'pending' || $status == 'processing') {
                $result['openPopup'] = true;
            } else {
                $result['openPopup'] = false;
            }
        }

        return $this->getResponse()->representJson($this->serializer->serialize($result));
    }
}