<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Mysql resource
 */
class Category extends AbstractDb
{
    /**
     * @var
     */
    protected $_categoryStoreTable;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * Category constructor.
     *
     * @param Context $context
     * @param DateTime $date
     * @param null $resourcePrefix
     */
    public function __construct(
        Context $context,
        DateTime $date,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_date = $date;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mb_categories', 'category_id');
        $this->_categoryStoreTable = $this->getTable('mb_event_category_store');
    }

    /**
     * @param AbstractModel $object
     * @return $this|AbstractDb
     */
    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);

        if (!$object->getId()) {
            return $this;
        }

        // load category available in stores
        $object->setStores($this->getStores((int)$object->getId()));

        return $this;
    }

    /**
     * Retrieve store IDs related to given rating
     *
     * @param int $categoryId
     * @return array
     */
    public function getStores($categoryId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_categoryStoreTable),
            'store_id'
        )->where(
            'category_id = ?',
            $categoryId
        );
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param AbstractModel $object
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->isObjectNew() && !$object->hasCreatedTime()) {
            $object->setCreatedTime($this->_date->gmtDate());
        }

        if ($object->hasData('stores') && !is_array($object->getStores())) {
            $object->setStores([$object->getStores()]);
        }

        return parent::_beforeSave($object);
    }

    /**
     * @param AbstractModel $object
     * @return $this|AbstractDb
     */
    protected function _afterSave(AbstractModel $object)
    {
        $connection = $this->getConnection();

        $stores = $object->getStores();
        if (!empty($stores)) {
            $condition = ['category_id = ?' => $object->getId()];
            $connection->delete($this->_categoryStoreTable, $condition);

            $insertedStoreIds = [];
            foreach ($stores as $storeId) {
                if (in_array($storeId, $insertedStoreIds)) {
                    continue;
                }

                $insertedStoreIds[] = $storeId;
                $storeInsert = ['store_id' => $storeId, 'category_id' => $object->getId()];
                $connection->insert($this->_categoryStoreTable, $storeInsert);
            }
        }

        return $this;
    }
}
