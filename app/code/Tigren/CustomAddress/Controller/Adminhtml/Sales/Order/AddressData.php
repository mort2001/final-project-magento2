<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Controller\Adminhtml\Sales\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

/**
 * Class AddressData
 * @package Tigren\CustomAddress\Controller\Adminhtml\Sales\Order
 */
class AddressData extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * AddressData constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        $response = ['success' => false, 'message' => ''];

        if ($orderId) {
            /**
             * @var Order $order
             */
            $order = $this->orderFactory->create()->load($orderId);

            if ($order->getId()) {
                $response['success'] = true;

                $shippingAddress = $order->getShippingAddress();
                $billingAddress = $order->getBillingAddress();

                $response['shippingAddress'] = [
                    'entity_id' => $shippingAddress->getEntityId(),
                    'parent_id' => $shippingAddress->getParentId(),
                    'firstname' => $shippingAddress->getFirstname(),
                    'lastname' => $shippingAddress->getLastname(),
                    'telephone' => $shippingAddress->getTelephone(),
                    'street' => $shippingAddress->getStreet(),
                    'country_id' => $shippingAddress->getCountryId(),
                    'region' => $shippingAddress->getRegion(),
                    'region_id' => $shippingAddress->getRegionId(),
                    'city' => $shippingAddress->getCity(),
                    'city_id' => $shippingAddress->getCityId(),
                    'subdistrict' => $shippingAddress->getSubdistrict(),
                    'subdistrict_id' => $shippingAddress->getSubdistrictId(),
                    'postcode' => $shippingAddress->getPostcode()
                ];

                $response['billingAddress'] = [
                    'entity_id' => $billingAddress->getEntityId(),
                    'parent_id' => $shippingAddress->getParentId(),
                    'firstname' => $billingAddress->getFirstname(),
                    'lastname' => $billingAddress->getLastname(),
                    'telephone' => $billingAddress->getTelephone(),
                    'street' => $billingAddress->getStreet(),
                    'country_id' => $billingAddress->getCountryId(),
                    'region' => $billingAddress->getRegion(),
                    'region_id' => $billingAddress->getRegionId(),
                    'city' => $billingAddress->getCity(),
                    'city_id' => $billingAddress->getCityId(),
                    'subdistrict' => $billingAddress->getSubdistrict(),
                    'subdistrict_id' => $billingAddress->getSubdistrictId(),
                    'postcode' => $billingAddress->getPostcode()
                ];

                return $this->resultJsonFactory->create()->setData($response);
            }
        }

        $response['message'] = __('Cannot get order addresses data.');

        return $this->resultJsonFactory->create()->setData($response);
    }
}
