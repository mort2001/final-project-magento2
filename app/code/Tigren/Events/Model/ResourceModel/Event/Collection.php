<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\ResourceModel\Event;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Collection
 *
 * @package Tigren\Events\Model\ResourceModel\Event
 */
class Collection extends AbstractCollection
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Collection constructor.
     *
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Set store filter
     *
     * @param int $storeIds
     * @return $this
     */
    public function setStoreFilter($storeIds)
    {
        if ($this->_storeManager->isSingleStoreMode()) {
            return $this;
        }
        $connection = $this->getConnection();
        if (!is_array($storeIds)) {
            $storeIds = [$storeIds === null ? -1 : $storeIds];
        }
        if (empty($storeIds)) {
            return $this;
        }
        $this->getSelect()->distinct(true)->join(
            ['store_table' => $this->getTable('mb_event_store')],
            'main_table.event_id = store_table.event_id',
            []
        );
        $inCondition = $connection->prepareSqlCondition('store_table.store_id', ['in' => $storeIds]);
        $this->getSelect()->where($inCondition);
        return $this;
    }

    /**
     * @param  $catId
     * @return $this
     */
    public function setCatFilter($catId)
    {
        $connection = $this->getConnection();

        if (empty($catId)) {
            return $this;
        }
        $this->getSelect()->distinct(true)->join(
            ['cat_table' => $this->getTable('mb_event_category')],
            'main_table.event_id = cat_table.event_id',
            []
        );
        $inCondition = $connection->prepareSqlCondition('cat_table.category_id', $catId);
        $this->getSelect()->where($inCondition)
            ->group(['main_table.event_id']);
        return $this;
    }

    /**
     * @param  $eventSearch
     * @return $this
     */
    public function setEventNameFilter($eventSearch)
    {
        $this->getSelect()->where("title LIKE '%$eventSearch%'");
        return $this;
    }

    /**
     * @param  $locationSearch
     * @return $this
     */
    public function setLocationFilter($locationSearch)
    {
        $this->getSelect()->where("location LIKE '%$locationSearch%'");
        return $this;
    }

    /**
     * @return $this
     */
    public function setUpcomingFilter()
    {
        $this->getSelect()->where("TIMESTAMPDIFF(SECOND,UTC_TIMESTAMP(),main_table.start_time) > 0");
        return $this;
    }

    /**
     * @param  $customerId
     * @return $this
     */
    public function setFavoriteFilter($customerId)
    {
        $connection = $this->getConnection();
        $this->getSelect()->distinct(true)->join(
            ['fav_table' => $this->getTable('mb_event_favorite')],
            'main_table.event_id = fav_table.event_id',
            []
        );
        $inCondition = $connection->prepareSqlCondition('fav_table.customer_id', $customerId);
        $this->getSelect()->where($inCondition)
            ->group(['main_table.event_id']);
        return $this;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Tigren\Events\Model\Event', 'Tigren\Events\Model\ResourceModel\Event');
        $this->_idFieldName = 'event_id';
    }
}
