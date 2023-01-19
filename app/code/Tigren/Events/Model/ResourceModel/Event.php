<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\ResourceModel;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Tigren\Events\Helper\Data;

/**
 * Mysql resource
 */
class Event extends AbstractDb
{
    /**
     * @var
     */
    protected $_eventStoreTable;

    /**
     * @var
     */
    protected $_categoryEventTable;

    /**
     * @var
     */
    protected $_participantTable;

    /**
     * @var
     */
    protected $_productEventTable;

    /**
     * @var
     */
    protected $_favoriteTable;

    /**
     * @var
     */
    protected $_customerTable;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * Event constructor.
     *
     * @param Context $context
     * @param DateTime $date
     * @param ProductFactory $productFactory
     * @param Data $helper
     * @param null $resourcePrefix
     */
    public function __construct(
        Context $context,
        DateTime $date,
        ProductFactory $productFactory,
        Data $helper,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->_date = $date;
        $this->_productFactory = $productFactory;
        $this->_helper = $helper;
    }

    /**
     * @param  $eventId
     * @return array
     */
    public function getParticipantIds($eventId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_participantTable),
            'participant_id'
        )
            ->where('event_id = ?', $eventId);
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param  $eventId
     * @return array
     */
    public function getFavoritedCustomerIds($eventId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_favoriteTable),
            'customer_id'
        )
            ->where('event_id = ?', $eventId);
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param $eventId
     * @param $customerId
     */
    public function addFavorite($eventId, $customerId)
    {
        $connection = $this->getConnection();
        $favInsert = ['event_id' => $eventId, 'customer_id' => $customerId];
        $connection->insert($this->_favoriteTable, $favInsert);
    }

    /**
     * @param $eventId
     * @param $customerId
     */
    public function removeFavorite($eventId, $customerId)
    {
        $connection = $this->getConnection();
        $favCondition = ['event_id=?' => $eventId, 'customer_id=?' => $customerId];
        $connection->delete($this->_favoriteTable, $favCondition);
    }

    /**
     * @param  $productId
     * @return string
     */
    public function getEventAssociatedPrd($productId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_productEventTable),
            'event_id'
        )
            ->where('entity_id = ?', $productId);
        return $this->getConnection()->fetchOne($select);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('mb_events', 'event_id');
        $this->_eventStoreTable = $this->getTable('mb_event_store');
        $this->_categoryEventTable = $this->getTable('mb_event_category');
        $this->_participantTable = $this->getTable('mb_participants');
        $this->_productEventTable = $this->getTable('mb_event_product');
        $this->_favoriteTable = $this->getTable('mb_event_favorite');
        $this->_customerTable = $this->getTable('customer_entity');
    }

    /**
     * @param AbstractModel $object
     * @return $this|AbstractDb
     * @throws \Exception
     */
    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);
        if (!$object->getId()) {   //if create new
            return $this;
        }

        // Correct date time
        if ($object->hasStartTime()) {
            $startTime = $this->_helper->convertTime($object->getStartTime(), true);
            $object->setStartTime($startTime);
        }
        if ($object->hasEndTime()) {
            $endTime = $this->_helper->convertTime($object->getEndTime(), true);
            $object->setEndTime($endTime);
        }
        if ($object->hasRegistrationDeadline()) {
            $registrationDeadline = $this->_helper->convertTime($object->getRegistrationDeadline(), true);
            $object->setRegistrationDeadline($registrationDeadline);
        }

        if ($object->getId()) {
            // load event available in stores
            $object->setStores($this->getStoreIds((int)$object->getId()));
            // load categories associate to this event
            $object->setCategories($this->getCategoryIds((int)$object->getId()));
            // load product associate to this event
            $object->setProductId($this->getProductId((int)$object->getId()));
            $object->setProduct($this->getProduct((int)$object->getId()));
        }

        return $this;
    }

    /**
     * @param  $eventId
     * @return array
     */
    public function getStoreIds($eventId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_eventStoreTable),
            'store_id'
        )
            ->where('event_id = ?', $eventId);
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param  $eventId
     * @return array
     */
    public function getCategoryIds($eventId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_categoryEventTable),
            'category_id'
        )
            ->where('event_id = ?', $eventId);
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param  $eventId
     * @return string
     */
    public function getProductId($eventId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_productEventTable),
            'entity_id'
        )
            ->where('event_id = ?', $eventId);
        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param  $eventId
     * @return null
     */
    public function getProduct($eventId)
    {
        $productId = $this->getProductId($eventId);
        if ($productId) {
            return $this->_productFactory->create()->load($productId);
        } else {
            return null;
        }
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

        //Save event_stores
        $stores = $object->getStores();
        if (!empty($stores)) {
            $condition = ['event_id = ?' => $object->getId()];
            $connection->delete($this->_eventStoreTable, $condition);

            $insertedStoreIds = [];
            foreach ($stores as $storeId) {
                if (in_array($storeId, $insertedStoreIds)) {
                    continue;
                }

                $insertedStoreIds[] = $storeId;
                $storeInsert = ['store_id' => $storeId, 'event_id' => $object->getId()];
                $connection->insert($this->_eventStoreTable, $storeInsert);
            }
        }

        //save event_categories
        $categories = $object->getCategories();
        if (!($categories === null)) {
            $condition = ['event_id = ?' => (int)$object->getId()];
            $connection->delete($this->_categoryEventTable, $condition);

            $insertedCategoryIds = [];
            if ($categories) {
                foreach ($categories as $categoryId) {
                    if (in_array($categoryId, $insertedCategoryIds)) {
                        continue;
                    }

                    $insertedCategoryIds[] = $categoryId;
                    $categoryInsert = ['category_id' => $categoryId, 'event_id' => $object->getId()];
                    $connection->insert($this->_categoryEventTable, $categoryInsert);
                }
            }
        }

        //save event_product
        $productId = $object->getProductAssociated();
        if (!empty($productId)) {
            $condition = ['event_id = ?' => $object->getId()];
            $connection->delete($this->_productEventTable, $condition);

            $productInsert = ['entity_id' => $productId, 'event_id' => $object->getId()];
            $connection->insert($this->_productEventTable, $productInsert);
        }

        return $this;
    }
}
