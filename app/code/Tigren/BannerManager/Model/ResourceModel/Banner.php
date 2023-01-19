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
use Tigren\BannerManager\Helper\Data;

/**
 * BannerManager banner mysql resource
 */
class Banner extends AbstractDb
{
    /**
     * Block banner entity table
     *
     * @var string
     */
    protected $_blockBannerTable;

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
     * @param TimezoneInterface $localeDate
     * @param Data $bannerManagerHelper
     * @param string|null $resourcePrefix
     */
    public function __construct(
        Context $context,
        TimezoneInterface $localeDate,
        Data $bannerManagerHelper,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_localeDate = $localeDate;
        $this->_bannerManangerHelper = $bannerManagerHelper;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tigren_bannermanager_banner', 'banner_id');
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

        $object->setBlocks($this->getBlocks((int)$object->getId()));

        // Correct datetime
        if ($object->hasStartTime()) {
            $startTime = $this->_bannerManangerHelper->convertDateTime($object->getStartTime(), true);
            $object->setStartTime($startTime);
        }
        if ($object->hasEndTime()) {
            $endTime = $this->_bannerManangerHelper->convertDateTime($object->getEndTime(), true);
            $object->setEndTime($endTime);
        }

        return $this;
    }

    /**
     * Retrieve block IDs related
     *
     * @param int $bannerId
     * @return array
     */
    public function getBlocks($bannerId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_blockBannerTable),
            'block_id'
        )->where(
            'banner_id = ?',
            $bannerId
        );
        return $this->getConnection()->fetchCol($select);
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
         * save blocks
         */
        $blocks = $object->getBlocks();
        if (!empty($blocks)) {
            $condition = ['banner_id = ?' => $object->getId()];
            $connection->delete($this->_blockBannerTable, $condition);

            $insertedBlockIds = [];
            foreach ($blocks as $blockId) {
                if (in_array($blockId, $insertedBlockIds)) {
                    continue;
                }

                $insertedBlockIds[] = $blockId;
                $blockInsert = ['block_id' => $blockId, 'banner_id' => $object->getId()];
                $connection->insert($this->_blockBannerTable, $blockInsert);
            }
        }

        return $this;
    }
}
