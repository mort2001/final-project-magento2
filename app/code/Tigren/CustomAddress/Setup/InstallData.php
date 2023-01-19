<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Setup;

use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;

/**
 * Class InstallData
 * @package Tigren\CustomAddress\Setup
 */
class InstallData implements InstallDataInterface
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
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
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
    }
}
