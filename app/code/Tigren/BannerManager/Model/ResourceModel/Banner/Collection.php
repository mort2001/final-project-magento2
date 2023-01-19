<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\ResourceModel\Banner;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;

/**
 * Class Collection
 *
 * @package Tigren\BannerManager\Model\ResourceModel\Banner
 */
class Collection extends AbstractCollection
{
    /**
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param mixed $connection
     * @param AbstractDb $resource
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
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
        return $this->_toOptionArray('banner_id', 'banner_title');
    }

    /**
     * @param  $blockId
     * @return $this
     */
    public function getBannerByBlock($blockId)
    {
        $this->getSelect()->join(
            ['banner_block' => 'tigren_bannermanager_block_banner_entity'],
            sprintf('banner_block.banner_id = main_table.banner_id AND banner_block.block_id = %s', $blockId),
            ['position']
        );
        return $this;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Tigren\BannerManager\Model\Banner', 'Tigren\BannerManager\Model\ResourceModel\Banner');
        $this->_idFieldName = 'banner_id';
    }
}
