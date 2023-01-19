<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\ResourceModel\Block;

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
 * @package Tigren\BannerManager\Model\ResourceModel\Block
 */
class Collection extends AbstractCollection
{
    /**
     * @var
     */
    protected $_isStoreJoined;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param mixed $connection
     * @param AbstractDb $resource
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
     * @param int $storeId
     * @return $this
     */
    public function setStoreFilter($storeId)
    {
        if ($this->_storeManager->isSingleStoreMode()) {
            return $this;
        }

        $connection = $this->getConnection();
        if (!is_array($storeId)) {
            $storeId = [$storeId === null ? -1 : $storeId];
        }
        if (empty($storeId)) {
            return $this;
        }
        if (!$this->_isStoreJoined) {
            $this->getSelect()->distinct(
                true
            )->join(
                ['store' => $this->getTable('tigren_bannermanager_block_store')],
                'main_table.block_id = store.block_id',
                []
            );
            $this->_isStoreJoined = true;
        }
        $inCondition = $connection->prepareSqlCondition('store.store_id', ['in' => $storeId]);
        $this->getSelect()->where($inCondition);
        $this->setPositionOrder();
        return $this;
    }

    /**
     * Set order by position field
     *
     * @param string $dir
     * @return $this
     */
    public function setPositionOrder($dir = 'ASC')
    {
        $this->setOrder('main_table.sort_order', $dir);
        return $this;
    }

    /**
     * Set Active Filter
     *
     * @param bool $isActive
     * @return $this
     */
    public function setActiveFilter($isActive = true)
    {
        $this->getSelect()->where('main_table.is_active=?', $isActive);
        return $this;
    }

    /**
     * Get collection data as options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('block_id', 'block_title');
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Tigren\BannerManager\Model\Block', 'Tigren\BannerManager\Model\ResourceModel\Block');
        $this->_idFieldName = 'block_id';
    }
}
