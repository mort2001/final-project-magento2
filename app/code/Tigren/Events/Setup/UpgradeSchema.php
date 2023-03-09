<?php

namespace Tigren\Events\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.3.1', '<')) {
            $connection = $setup->getConnection();
            $connection->addColumn(
                $setup->getTable('mb_events'),
                'require_time',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'unsigned' => true,
                    'default' => '1',
                    'comment' => 'Require Time Event'
                ]
            );
        }
        $setup->endSetup();
    }
}
