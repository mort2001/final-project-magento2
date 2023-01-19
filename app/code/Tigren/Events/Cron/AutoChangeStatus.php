<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Cron;

use Exception;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Tigren\Events\Model\EventFactory;
use Tigren\Events\Model\ResourceModel\Event\CollectionFactory;

/**
 * Backend event observer
 */
class AutoChangeStatus
{
    /**
     *
     * @var DateTime
     */
    protected $_date;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventFactory
     */
    private $_eventFactory;

    /**
     * @param DateTime $date
     * @param CollectionFactory $eventFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        DateTime $date,
        CollectionFactory $eventFactory,
        LoggerInterface $logger
    ) {
        $this->_date = $date;
        $this->_eventFactory = $eventFactory;
        $this->logger = $logger;
    }

    /**
     * Cron job method to change status in time
     *
     * @return void
     */
    public function execute()
    {
        $events = $this->_eventFactory->create()->getCollection();
        if (count($events)) {
            try {
                foreach ($events as $event) {
                    $now = $this->_date->gmtDate();
                    $startTime = $event->getStartTime();
                    $endTime = $event->getEndTime();
                    if ($endTime < $now) {
                        $status = 'expired';
                    } else {
                        if ($startTime < $now && $now < $endTime) {
                            $status = 'happening';
                        } else {
                            if ($now < $startTime) {
                                $status = 'upcoming';
                            }
                        }
                    }

                    $data = ['event_id' => $event->getId(), 'progress_status' => $status];
                    $event->setData($data);
                    $event->save();
                }
            } catch (Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}
