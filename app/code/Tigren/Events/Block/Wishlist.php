<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Html\Link;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Tigren\Events\Helper\Data;
use Tigren\Events\Model\EventFactory;

/**
 * Class Wishlist
 *
 * @package Tigren\Events\Block
 */
class Wishlist extends Link
{
    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * @var Data
     */
    protected $_eventsHelper;

    /**
     * @var null
     */
    protected $_events;

    /**
     * Wishlist constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param EventFactory $eventFactory
     * @param Data $eventsHelper
     * @param array $data
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        EventFactory $eventFactory,
        Data $eventsHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_eventFactory = $eventFactory;
        $this->_eventsHelper = $eventsHelper;

        $this->_events = $this->getEvents();
    }

    /**
     * @return null
     * @throws NoSuchEntityException
     */
    public function getEvents()
    {
        $customerId = $this->getCustomerId();
        if (empty($customerId)) {
            return null;
        }

        $storeIds = [0, $this->getCurrentStoreId()];
        $collection = $this->_eventFactory->create()->getCollection()
            ->addFieldToFilter('status', 1)
            ->setOrder('start_time', 'ASC')
            ->setFavoriteFilter($customerId)
            ->setStoreFilter($storeIds);
        return $collection;
    }

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->_eventsHelper->getCustomerId();
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
     * @return null
     */
    public function getPagedEvents()
    {
        return $this->_events;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @param  $event
     * @return string
     */
    public function getAvatarUrl($event)
    {
        $avatarUrl = $event->getAvatarUrl();
        if ($avatarUrl == '') {
            $avatarUrl = $this->getViewFileUrl('Tigren_Events::images/default_event.jpg');
        }
        return $avatarUrl;
    }

    /**
     * @param  $event
     * @return bool|string
     */
    public function getShortDescription($event)
    {
        if ($event->getDescription() !== null) {
            $description = substr($event->getDescription(), 0, 100);
            if (strlen($event->getDescription()) > 100) {
                $description .= '.....';
            }
        } else {
            $description = ' ';
        }

        return $description;
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
     * @return $this|Link
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->_events) {
            $pager = $this->getLayout()->createBlock('Magento\Theme\Block\Html\Pager', 'events.event.index.pager')
                ->setAvailableLimit([10 => 10, 20 => 20, 50 => 50, 100 => 100])
                ->setCollection($this->_events);
            $this->setChild('pager', $pager);
            $this->_events->load();
        }
        return $this;
    }
}
