<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Tigren\Events\Model\EventFactory;

/**
 * Class ProductDeleteAfter
 *
 * @package Tigren\Events\Observer
 */
class ProductDeleteAfter implements ObserverInterface
{
    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * ProductDeleteAfter constructor.
     *
     * @param EventFactory $eventFactory
     */
    public function __construct(
        EventFactory $eventFactory
    ) {
        $this->_eventFactory = $eventFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $productId = (int)$observer->getProduct()->getId();
        $event = $this->_eventFactory->create();
        $eventId = $event->getEventAssociatedPrd($productId);
        $event->load($eventId);
        $event->delete();
    }
}
