<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Tigren\Events\Model\Event;
use Tigren\Events\Model\EventFactory;

/**
 * Class Sidebar
 *
 * @package Tigren\Events\Block
 */
class Sidebar extends Template
{
    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * Sidebar constructor.
     *
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_eventFactory = $eventFactory;
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [Event::CACHE_TAG . '_' . 'sidebar'];
    }

    /**
     * @return bool
     */
    public function isShowUpcomingEvents()
    {
        $isShowUpcomingEvents = $this->getScopeConfig('events/general_setting/show_upcoming_events');
        if ($isShowUpcomingEvents && $isShowUpcomingEvents == '1') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param  $path
     * @return mixed
     */
    public function getScopeConfig($path)
    {
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getUpcomingEventCollection()
    {
        $storeIds = [0, $this->getCurrentStoreId()];
        $collection = $this->_eventFactory->create()->getCollection()
            ->addFieldToFilter('status', 1)
            ->setUpcomingFilter()
            ->setOrder('start_time', 'ASC')
            ->setStoreFilter($storeIds);
        return $collection;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }

    /**
     * @param  $event
     * @return string
     */
    public function getEventUrl($event)
    {
        return $this->getUrl('events/index/view', ['event_id' => $event->getId()]);
    }
}
