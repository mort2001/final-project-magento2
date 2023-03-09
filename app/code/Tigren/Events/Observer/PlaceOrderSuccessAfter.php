<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2023 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory as OrderAddressCollectionFactory;

class PlaceOrderSuccessAfter implements ObserverInterface
{
    /**
     * @var OrderAddressCollectionFactory
     */
    private $orderAddressFactory;

    /**
     * @var QuoteItemCollectionFactory
     */
    private $quoteItemCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $_resource;

    /**
     * @param OrderAddressCollectionFactory $orderAddressFactory
     * @param QuoteItemCollectionFactory $quoteItemCollectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        OrderAddressCollectionFactory $orderAddressFactory,
        QuoteItemCollectionFactory $quoteItemCollectionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->orderAddressFactory = $orderAddressFactory;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->_resource = $resourceConnection;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderedProducts = $this->quoteItemCollectionFactory->create()->addFieldToFilter(
            'quote_id',
            $order->getQuoteId()
        );
        foreach ($orderedProducts as $orderedProduct) {
            $productsIds[] = $orderedProduct->getProductId();
        }
        $connection = $this->_resource->getConnection();
        $eventIds = $connection->select()->from($connection->getTableName('mb_event_product'), ['event_id'])
            ->where('entity_id IN (?)', $productsIds);

        $participants = [];
        $orderAddresses = $order->getAddresses();
        $orderAddress = $orderAddresses[0];
        foreach ($connection->fetchAll($eventIds) as $eventId) {
            $participants['email'] = $orderAddress['email'];
            $participants['fullname'] = $orderAddress['firstname'] . ' ' . $orderAddress['lastname'];
            $participants['address'] = $orderAddress['street'] . ' ' . $orderAddress['subdistrict'] . ' ' . $orderAddress['city'] . ' '
                . $orderAddress['region'] . ' ' . $orderAddress['country_id'];
            $participants['phone'] = $orderAddress['telephone'];
            $participants['status'] = 0;
            $connection->insert($connection->getTableName('mb_participants'), array_merge($participants, $eventId));
        }
    }
}
