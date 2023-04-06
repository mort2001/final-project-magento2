<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Setup;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Quote\Setup\QuoteSetup;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Zend_Validate_Exception;

/**
 * Class UpgradeData
 * @package Tigren\CustomTheme\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;

    /**
     * Constructor
     *
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SalesSetupFactory $salesSetupFactory
     * @param QuoteSetupFactory $quoteSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/a.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('message '.print_r('upgrade', true));
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $customerSetup->addAttribute(AttributeProvider::ENTITY, 'city_id', [
                'label' => 'City',
                'input' => 'hidden',
                'type' => 'static',
                'required' => false,
                'position' => 1000,
                'visible' => true,
                'system' => true,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
                'backend' => ''
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'city_id')
                ->addData([
                    'used_in_forms' => [
                        'customer_address_edit',
                        'customer_register_address'
                    ]
                ]);
            $attribute->save();

            $customerSetup->addAttribute(AttributeProvider::ENTITY, 'subdistrict', [
                'label' => 'Sub District',
                'input' => 'text',
                'type' => 'static',
                'required' => false,
                'position' => 1010,
                'visible' => true,
                'system' => true,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
                'backend' => ''
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'subdistrict')
                ->addData([
                    'used_in_forms' => [
                        'adminhtml_customer_address',
                        'customer_address_edit',
                        'customer_register_address'
                    ]
                ]);
            $attribute->save();

            $customerSetup->addAttribute(
                AttributeProvider::ENTITY,
                'subdistrict_id',
                [
                    'label' => 'Sub District',
                    'input' => 'hidden',
                    'type' => 'static',
                    'required' => false,
                    'position' => 1020,
                    'visible' => true,
                    'system' => true,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_searchable_in_grid' => false,
                    'backend' => ''
                ]
            );
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'subdistrict_id')
                ->addData([
                    'used_in_forms' => [
                        'customer_address_edit',
                        'customer_register_address'
                    ]
                ]);
            $attribute->save();

            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
            $salesSetup->addAttribute('order_address', 'city_id', ['type' => 'int', 'input' => 'hidden']);
            $salesSetup->addAttribute('order_address', 'subdistrict_id', ['type' => 'int', 'input' => 'hidden']);
            $salesSetup->addAttribute(
                'order_address',
                'subdistrict',
                ['type' => 'varchar', 'input' => 'text', 'length' => 255]
            );

            $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);
            $quoteSetup->addAttribute('quote_address', 'city_id', ['type' => 'int', 'input' => 'hidden']);
            $quoteSetup->addAttribute('quote_address', 'subdistrict_id', ['type' => 'int', 'input' => 'hidden']);
            $quoteSetup->addAttribute(
                'quote_address',
                'subdistrict',
                ['type' => 'varchar', 'input' => 'text', 'length' => 255]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('customer_address_entity'),
                'city_id',
                [
                    'comment' => 'City Id',
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'default' => null
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('customer_address_entity'),
                'subdistrict',
                [
                    'comment' => 'Sub District',
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default' => null
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('customer_address_entity'),
                'subdistrict_id',
                [
                    'comment' => 'Sub District ID',
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => true,
                    'default' => null
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'city_id')
                ->addData([
                    'used_in_forms' => [
                        'adminhtml_customer_address',
                        'customer_address_edit',
                        'customer_register_address'
                    ]
                ]);
            $attribute->save();

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'subdistrict_id')
                ->addData([
                    'used_in_forms' => [
                        'adminhtml_customer_address',
                        'customer_address_edit',
                        'customer_register_address'
                    ]
                ]);
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            /** @var CategorySetup $salesSetup * */
            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
            $salesSetup->addAttribute(
                'order_address',
                'is_full_invoice',
                ['type' => 'int', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'head_office',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'company_name',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'phone',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'branch_office',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'branch',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'tax_identification_number',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'personal_firstname',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'personal_lastname',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $salesSetup->addAttribute(
                'order_address',
                'invoice_type',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );

            /** @var QuoteSetup $quoteSetup * */
            $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);
            $quoteSetup->addAttribute(
                'quote_address',
                'is_full_invoice',
                ['type' => 'int', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'company_name',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'phone',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'head_office',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'branch_office',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'branch',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'tax_identification_number',
                ['type' => 'varchar', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'personal_firstname',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'personal_lastname',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
            $quoteSetup->addAttribute(
                'quote_address',
                'invoice_type',
                ['type' => 'text', 'input' => 'hidden', 'is_user_defined' => 0]
            );
        }

        $installer->endSetup();
    }
}
