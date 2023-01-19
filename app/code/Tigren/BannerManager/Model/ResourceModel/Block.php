<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Model\ResourceModel;

use Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\BannerManager\Helper\Data;

/**
 * BannerManager block mysql resource
 */
class Block extends AbstractDb
{
    /**
     * Block store table
     *
     * @var string
     */
    protected $_blockStoreTable;

    /**
     * Block banner entity table
     *
     * @var string
     */
    protected $_blockBannerTable;

    /**
     * Core model store manager interface
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var Data
     */
    protected $_bannerManangerHelper;

    /**
     * Construct
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $localeDate
     * @param Data $bannerManagerHelper
     * @param string|null $resourcePrefix
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        Data $bannerManagerHelper,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->_bannerManangerHelper = $bannerManagerHelper;
    }

    /**
     * @param  $blockId
     * @param  $banenrId
     * @return array
     */
    public function getBannerPosition($blockId, $banenrId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_blockBannerTable),
            'position'
        )->where(
            'block_id = ?',
            $blockId
        );
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param  $blockId
     * @return array
     */
    public function getBannersPosition($blockId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_blockBannerTable),
            ['banner_id', 'position']
        )->where(
            'block_id = ?',
            $blockId
        );
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tigren_bannermanager_block', 'block_id');
        $this->_blockStoreTable = $this->getTable('tigren_bannermanager_block_store');
        $this->_blockBannerTable = $this->getTable('tigren_bannermanager_block_banner_entity');
    }

    /**
     * Actions after load
     *
     * @param AbstractModel|\Tigren\BannerManager\Model\Block $object
     * @return $this
     * @throws Exception
     */
    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);

        if (!$object->getId()) {
            return $this;
        }

        // load block available in stores
        $object->setStores($this->getStores((int)$object->getId()));

        $object->setCustomerGroupIds(explode(',', $object->getCustomerGroupIds()));

        $object->setBanners($this->getBanners((int)$object->getId()));

        // Correct datetime
        if ($object->hasFromDate()) {
            $fromDate = $this->_bannerManangerHelper->convertDateTime($object->getFromDate(), true);
            $object->setFromDate($fromDate);
        }
        if ($object->hasToDate()) {
            $toDate = $this->_bannerManangerHelper->convertDateTime($object->getToDate(), true);
            $object->setToDate($toDate);
        }

        return $this;
    }

    /**
     * Retrieve store IDs related to given rating
     *
     * @param int $blockId
     * @return array
     */
    public function getStores($blockId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_blockStoreTable),
            'store_id'
        )->where(
            'block_id = ?',
            $blockId
        );
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve banner IDs related
     *
     * @param int $blockId
     * @return array
     */
    public function getBanners($blockId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_blockBannerTable),
            'banner_id'
        )->where(
            'block_id = ?',
            $blockId
        );
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Perform actions before object save
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->hasData('stores') && is_array($object->getStores())) {
            $stores = $object->getStores();
            $stores[] = 0;
            $object->setStores($stores);
        } elseif ($object->hasData('stores')) {
            $object->setStores([$object->getStores(), 0]);
        }

        if ($object->hasData('customer_group_ids') && is_array($object->getCustomerGroupIds())) {
            $object->setCustomerGroupIds(implode(',', $object->getCustomerGroupIds()));
        } elseif ($object->hasData('customer_group_ids')) {
            $object->setCustomerGroupIds($object->getCustomerGroupIds());
        }

        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $connection = $this->getConnection();

        /**
         * save stores
         */
        $stores = $object->getStores();
        if (!empty($stores)) {
            $condition = ['block_id = ?' => $object->getId()];
            $connection->delete($this->_blockStoreTable, $condition);

            $insertedStoreIds = [];
            foreach ($stores as $storeId) {
                if (in_array($storeId, $insertedStoreIds)) {
                    continue;
                }

                $insertedStoreIds[] = $storeId;
                $storeInsert = ['store_id' => $storeId, 'block_id' => $object->getId()];
                $connection->insert($this->_blockStoreTable, $storeInsert);
            }
        }

        /**
         * save banners
         */
        $banners = $object->getBanners();
        if (!empty($banners)) {
            $condition = ['block_id = ?' => $object->getId()];
            $connection->delete($this->_blockBannerTable, $condition);

            $insertedBannerIds = [];
            foreach ($banners as $bannerId => $position) {
                if (in_array($bannerId, $insertedBannerIds)) {
                    continue;
                }

                $insertedBannerIds[] = $bannerId;
                $bannerInsert = [
                    'banner_id' => $bannerId,
                    'block_id' => $object->getId(),
                    'position' => isset($position['position']) ? $position['position'] : ''
                ];
                $connection->insert($this->_blockBannerTable, $bannerInsert);
            }
        }

        return $this;
    }
}
