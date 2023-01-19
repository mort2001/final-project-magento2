<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

/**
 * Class InstallSchema
 *
 * @package Tigren\Events\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'mb_events'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_events'))
            ->addColumn(
                'event_id',
                Table::TYPE_INTEGER,
                11,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn('title', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('description', Table::TYPE_TEXT, null, [])
            ->addColumn('avatar', Table::TYPE_TEXT, 100, ['nullable' => false])
            ->addColumn('images', Table::TYPE_TEXT, null, ['nullable' => false])
            ->addColumn('video', Table::TYPE_TEXT, null, ['nullable' => false])
            ->addColumn('location', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('number_of_participant', Table::TYPE_INTEGER, 11, ['nullable' => false, 'default' => '0'])
            ->addColumn('allow_register', Table::TYPE_SMALLINT, 6, ['nullable' => false, 'default' => '1'])
            ->addColumn('registration_deadline', Table::TYPE_DATETIME, null, [])
            ->addColumn('created_time', Table::TYPE_DATETIME, null, [])
            ->addColumn('start_time', Table::TYPE_DATETIME, null, [])
            ->addColumn('end_time', Table::TYPE_DATETIME, null, [])
            ->addColumn('status', Table::TYPE_SMALLINT, 6, ['nullable' => false, 'default' => '1'])
            ->addColumn('progress_status', Table::TYPE_TEXT, 10, ['nullable' => false, 'default' => ''])
            ->addColumn('color', Table::TYPE_TEXT, 10, ['nullable' => false])
            ->addColumn('url_key', Table::TYPE_TEXT, 255, ['nullable' => true, 'default' => null])
            ->addColumn('is_show_contact', Table::TYPE_SMALLINT, 6, ['nullable' => false, 'default' => '1'])
            ->addColumn('contact_person', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('contact_phone', Table::TYPE_TEXT, 20, ['nullable' => false])
            ->addColumn('contact_email', Table::TYPE_TEXT, 100, ['nullable' => false])
            ->addColumn('contact_address', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->setComment('Events');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_categories'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_categories'))
            ->addColumn(
                'category_id',
                Table::TYPE_INTEGER,
                11,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn('category_title', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('category_description', Table::TYPE_TEXT, null, ['nullable' => false])
            ->addColumn('status', Table::TYPE_SMALLINT, 6, ['nullable' => false, 'default' => '1'])
            ->addColumn('created_time', Table::TYPE_DATETIME, null, [])
            ->setComment('Categories');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_event_category'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_event_category'))
            ->addColumn(
                'event_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'category_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addIndex(
                $installer->getIdxName('mb_event_category', ['category_id']),
                ['category_id']
            )
            ->addIndex(
                $installer->getIdxName('mb_event_category', ['event_id']),
                ['event_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_category', 'category_id', 'mb_categories', 'category_id'),
                'category_id',
                $installer->getTable('mb_categories'),
                'category_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_category', 'event_id', 'mb_events', 'event_id'),
                'event_id',
                $installer->getTable('mb_events'),
                'event_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Events Categories');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_event_category_store'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_event_category_store'))
            ->addColumn(
                'category_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                6,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addIndex(
                $installer->getIdxName('mb_event_category_store', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_category_store', 'category_id', 'mb_categories', 'category_id'),
                'category_id',
                $installer->getTable('mb_categories'),
                'category_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_category_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Category Store');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_event_store'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_event_store'))
            ->addColumn(
                'event_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                6,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addIndex(
                $installer->getIdxName('mb_event_store', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_store', 'event_id', 'mb_events', 'event_id'),
                'event_id',
                $installer->getTable('mb_events'),
                'event_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Event Store');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_participants'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_participants'))
            ->addColumn(
                'participant_id',
                Table::TYPE_INTEGER,
                11,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn('fullname', Table::TYPE_TEXT, 50, ['nullable' => false])
            ->addColumn('phone', Table::TYPE_TEXT, 20, ['nullable' => false])
            ->addColumn('email', Table::TYPE_TEXT, 100, ['nullable' => false])
            ->addColumn('address', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('event_id', Table::TYPE_INTEGER, 11, ['unsigned' => true, 'nullable' => false])
            ->addColumn('status', Table::TYPE_SMALLINT, 6, ['nullable' => false, 'default' => '1'])
            ->addIndex(
                $installer->getIdxName('mb_participants', ['event_id']),
                ['event_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_participants', 'event_id', 'mb_events', 'event_id'),
                'event_id',
                $installer->getTable('mb_events'),
                'event_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Participants');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_event_product'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_event_product'))
            ->addColumn(
                'event_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addIndex(
                $installer->getIdxName('mb_event_product', ['event_id']),
                ['event_id']
            )
            ->addIndex(
                $installer->getIdxName('mb_event_product', ['entity_id']),
                ['entity_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_product', 'event_id', 'mb_events', 'event_id'),
                'event_id',
                $installer->getTable('mb_events'),
                'event_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Event Product');
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_event_product'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_event_favorite'))
            ->addColumn(
                'event_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addIndex(
                $installer->getIdxName('mb_event_favorite', ['event_id']),
                ['event_id']
            )
            ->addIndex(
                $installer->getIdxName('mb_event_favorite', ['customer_id']),
                ['customer_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_favorite', 'event_id', 'mb_events', 'event_id'),
                'event_id',
                $installer->getTable('mb_events'),
                'event_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('mb_event_favorite', 'customer_id', 'customer_entity', 'entity_id'),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Event Favorite');
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
