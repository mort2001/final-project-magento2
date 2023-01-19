<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Widget\Block\BlockInterface;
use Tigren\Events\Helper\Data as EventsHelper;
use Tigren\Events\Model\CategoryFactory;
use Tigren\Events\Model\Event;
use Tigren\Events\Model\EventFactory;

/**
 * Class Events
 *
 * @package Tigren\Events\Block
 */
class Events extends Template implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = "widget/calendar.phtml";

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var
     */
    protected $_events;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var EventsHelper
     */
    protected $_eventsHelper;

    /**
     * Events constructor.
     *
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param CategoryFactory $categoryFactory
     * @param Registry $registry
     * @param EventsHelper $eventsHelper
     * @param ObjectManagerInterface $objectManager
     * @param DateTime $date
     * @param array $data
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        CategoryFactory $categoryFactory,
        Registry $registry,
        EventsHelper $eventsHelper,
        ObjectManagerInterface $objectManager,
        DateTime $date,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_objectManager = $objectManager;
        $this->_eventFactory = $eventFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_coreRegistry = $registry;
        $this->_eventsHelper = $eventsHelper;
        $this->_date = $date;
        $this->_events = $this->getEvents();
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getEvents()
    {
        $storeIds = [0, $this->getCurrentStoreId()];
        $collection = $this->_eventFactory->create()->getCollection()
            ->addFieldToFilter('status', 1)
            ->setOrder('start_time', 'ASC')
            ->setStoreFilter($storeIds);

        $catId = $this->getFilterCatId();
        $eventSearch = $this->getEventSearch();
        $locationSearch = $this->getLocationSearch();
        if ($catId) {
            $collection->setCatFilter($catId);
        }
        if ($eventSearch) {
            $collection->setEventNameFilter($eventSearch);
        }
        if ($locationSearch) {
            $collection->setLocationFilter($locationSearch);
        }
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
     * @return mixed
     */
    public function getFilterCatId()
    {
        return $this->_coreRegistry->registry('filter_cat_id');
    }

    /**
     * @return mixed
     */
    public function getEventSearch()
    {
        return $this->_coreRegistry->registry('event_search');
    }

    /**
     * @return mixed
     */
    public function getLocationSearch()
    {
        return $this->_coreRegistry->registry('location_search');
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [Event::CACHE_TAG . '_' . 'list'];
    }

    /**
     * @return mixed
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
     * @return false|string
     */
    public function getEventJson()
    {
        $defaultColor = '#3366CC';
        $collection = $this->_events;
        $results = [];

        if (count($collection)) {
            foreach ($collection as $event) {
                $item = [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'url' => $event->getEventUrl(),
                    'avatar_url' => $this->getAvatarUrl($event),
                    'reg_deadline' => $event->getRegistrationDeadline(),
                    'location' => $event->getLocation(),
                    'description' => $this->getShortDescription($event),
                ];
                $item['start'] = $this->_eventsHelper->convertTime($event->getStartTime(), true);
                $item['end'] = $this->_eventsHelper->convertTime($event->getEndTime(), true);
                $item['allDay'] = false;
                if ($this->getScopeConfig('events/calendar_setting/show_event_color') && $event->getColor() != '') {
                    $item['color'] = '#' . $event->getColor();
                } else {
                    $item['color'] = $defaultColor;
                }
                $results[] = $item;
            }
        }

        return json_encode($results);
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
     * @param  $time
     * @return false|string
     */
    public function getFormattedTime($time)
    {
        $time = $this->_eventsHelper->convertTime($time, true);
        $timestamp = $this->_date->timestamp($time);
        return date('M d, Y g:i A', $timestamp);
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getEventCategories()
    {
        $storeIds = [0, $this->getCurrentStoreId()];
        $collection = $this->_categoryFactory->create()->getCollection()
            ->addFieldToFilter('status', 1)
            ->setStoreFilter($storeIds);
        return $collection;
    }

    /**
     * @return $this|Template
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->_events && $this->getCurrentMode() === 'grid') {
            $pager = $this->getLayout()->createBlock('Magento\Theme\Block\Html\Pager', 'events.event.index.pager')
                ->setAvailableLimit([10 => 10, 20 => 20, 50 => 50, 100 => 100])
                ->setCollection($this->_events);
            $this->setChild('pager', $pager);
            $this->_events->load();
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrentMode()
    {
        $mode = $this->_coreRegistry->registry('current_view_mode');
        $availableMode = $this->getAvailableMode();
        $modes = array_keys($availableMode);
        $defaultMode = current($modes);

        if (!$mode || !isset($availableMode[$mode])) {
            $mode = $defaultMode;
        }

        return $mode;
    }

    /**
     * @return array
     */
    public function getAvailableMode()
    {
        switch ($this->getScopeConfig('events/general_setting/view_mode')) {
            case 'calendar':
                $availableMode = ['calendar' => 'Calendar'];
                break;

            case 'grid':
                $availableMode = ['grid' => 'Grid'];
                break;

            case 'calendar-grid':
                $availableMode = ['calendar' => 'Calendar', 'grid' => 'Grid'];
                break;

            case 'grid-calendar':
                $availableMode = ['grid' => 'Grid', 'calendar' => 'Calendar'];
                break;

            default:
                $availableMode = ['calendar' => 'Calendar', 'grid' => 'Grid'];
                break;
        }

        return $availableMode;
    }
}
