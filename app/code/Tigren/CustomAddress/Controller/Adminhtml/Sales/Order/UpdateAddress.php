<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Controller\Adminhtml\Sales\Order;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\LayoutFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\OrderFactory;

/**
 * Class UpdateAddress
 * @package Tigren\CustomAddress\Controller\Adminhtml\Sales\Order
 */
class UpdateAddress extends Action
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
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * UpdateAddress constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $response = ['success' => false, 'message' => '', 'backUrl' => false];

        $shippingAddressData = $this->getRequest()->getParam('shipping');
        $billingAddressData = $this->getRequest()->getParam('billing');

        if (!$shippingAddressData || !$billingAddressData
            || empty($shippingAddressData['parent_id']) || empty($billingAddressData['parent_id'])
            || empty($shippingAddressData['entity_id']) || empty($billingAddressData['entity_id'])
        ) {
            $response['message'] = __('Cannot find your requested data.');
            return $this->resultJsonFactory->create()->setData($response);
        }

        try {
            /** @var $shippingAddress OrderAddressInterface */
            $shippingAddress = $this->_objectManager->create(
                OrderAddressInterface::class
            )->load($shippingAddressData['entity_id']);

            if ($shippingAddress->getId()) {
                $shippingAddress->addData($shippingAddressData);

                $shippingAddressCustomAttributes = !empty($shippingAddressData['custom_attributes'])
                    ? $shippingAddressData['custom_attributes'] : [];
                foreach ($shippingAddressCustomAttributes as $key => $value) {
                    $shippingAddress->setData($key, $value);
                }

                $shippingAddress->save();
                $this->_eventManager->dispatch(
                    'admin_sales_order_address_update',
                    [
                        'order_id' => $shippingAddress->getParentId()
                    ]
                );
            } else {
                $response['message'] = __('Cannot update addresses data. Please try again.');
                return $this->resultJsonFactory->create()->setData($response);
            }

            /** @var $billingAddress OrderAddressInterface */
            $billingAddress = $this->_objectManager->create(
                OrderAddressInterface::class
            )->load($billingAddressData['entity_id']);
            if ($billingAddress->getId()) {
                $billingAddress->addData($billingAddressData);

                $billingAddressCustomAttributes = !empty($billingAddressData['custom_attributes'])
                    ? $billingAddressData['custom_attributes'] : [];
                foreach ($billingAddressCustomAttributes as $key => $value) {
                    $billingAddress->setData($key, $value);
                }

                $billingAddress->save();
                $this->_eventManager->dispatch(
                    'admin_sales_order_address_update',
                    [
                        'order_id' => $billingAddress->getParentId()
                    ]
                );
            } else {
                $response['message'] = __('Cannot update addresses data. Please try again.');
                return $this->resultJsonFactory->create()->setData($response);
            }

            $response['success'] = true;
            $response['backUrl'] = $this->_url->getUrl('omsmnp/orders/validate');
            $response['message'] = __('The order addresses were updated successfully.');
            return $this->resultJsonFactory->create()->setData($response);
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
            return $this->resultJsonFactory->create()->setData($response);
        }
    }
}
