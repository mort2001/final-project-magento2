<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddAttribute
 * @package Tigren\Question\Setup\Patch\Data
 */
class AddAttribute implements DataPatchInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * AccountPurposeCustomerAttribute constructor.
     *
     * @param ModuleDataSetupInterface $setup
     * @param Config $eavConfig
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        Config                   $eavConfig,
        CustomerSetupFactory     $customerSetupFactory
    )
    {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->setup = $setup;
        $this->eavConfig = $eavConfig;
    }

    /** We'll add our customer attribute here */
    /**
     * @return void
     * @throws LocalizedException
     */
    public function apply()
    {
        $customerSetup
            = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerEntity = $customerSetup->getEavConfig()
            ->getEntityType(Customer::ENTITY);
        $attributeSetId
            = $customerSetup->getDefaultAttributeSetId($customerEntity->getEntityTypeId());
        $attributeGroup
            = $customerSetup->getDefaultAttributeGroupId(
            $customerEntity->getEntityTypeId(),
            $attributeSetId
        );
        $customerSetup->addAttribute(Customer::ENTITY, 'is_question_created', [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Question is created',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'required' => false,
            'default' => "No",
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => true,
            'is_used_in_grid' => true,
            'is_filterable_in_grid' => true,
            'is_searchable_in_grid' => true,
            'position' => 300,
        ]);
        $newAttribute = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            'is_question_created'
        );
        $newAttribute->addData([
            'used_in_forms' => [
                'adminhtml_checkout',
                'adminhtml_customer',
                'customer_account_edit',
                'customer_account_create',
            ],
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup,
        ]);
        $newAttribute->save();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}