<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Catalog;

use Magento\Framework\App\ObjectManager;

/**
 * Class Product
 *
 * @package Tigren\Events\Model\Catalog
 */
class Product extends \Magento\Catalog\Model\Product
{
    /**
     * @return int
     */
    public function getOrderedQty()
    {
        $qty = 0;
        $orderItemCollection = $this->getOrderItemCollection();
        foreach ($orderItemCollection as $item) {
            $itemQty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();
            $qty += $itemQty;
        }
        return $qty;
    }

    /**
     * @return mixed
     */
    public function getOrderItemCollection()
    {
        $objectManager = ObjectManager::getInstance();
        $orderItemCollection = $objectManager->get('Magento\Sales\Model\ResourceModel\Order\Item\Collection')
            ->addFieldToFilter('product_id', $this->getId());
        return $orderItemCollection;
    }

    /**
     * @return mixed
     */
    public function getOrdererAddressCollection()
    {
        $objectManager = ObjectManager::getInstance();
        $orderItemCollection = $this->getOrderItemCollection();
        $orderIds = [];
        foreach ($orderItemCollection as $item) {
            $orderIds[] = $item->getOrderId();
        }
        $orderIds = array_unique($orderIds);

        $ordererAddressCollection = $objectManager->get('Magento\Sales\Model\ResourceModel\Order\Address\Collection')
            ->addAttributeToFilter('address_type', 'billing')
            ->addAttributeToFilter('parent_id', ['in' => $orderIds]);
        return $ordererAddressCollection;
    }
}
