<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tigren\CustomerGroupCatalog\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Customer\Model\Session;
use Zend_Log_Exception;

/**
 * Class Data
 * @package Tigren\CustomerGroupCatalog\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Session $session
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Session $session
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->_session = $session;
        parent::__construct($context);
    }

    /**
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRule()
    {
        $group_id = $this->_session->getCustomerGroupId();
        $ruleCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_group_ids', ['like' => '%' . $group_id . '%'])
            ->addFieldToFilter('from_date', ['lt' => date('Y-m-d')])
            ->addFieldToFilter('to_date', ['gt' => date('Y-m-d')])
            ->setOrder('priority', 'ASC')
            ->setOrder('discount_amount', 'DESC');

        return $ruleCollection->setPageSize(1)->getFirstItem();
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRules()
    {
        $group_id = $this->_session->getCustomerGroupId();
        $ruleCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_group_ids', ['like' => '%' . $group_id . '%'])
            ->addFieldToFilter('from_date', ['lt' => date('Y-m-d')])
            ->addFieldToFilter('to_date', ['gt' => date('Y-m-d')])
            ->setOrder('priority', 'ASC');

        return $ruleCollection->getData();
    }

    /**
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException|Zend_Log_Exception
     */
    public function getDiscount()
    {
        $group_id = $this->_session->getCustomerGroupId();
        $ruleCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_group_ids', ['like' => '%' . $group_id . '%'])
            ->addFieldToFilter('from_date', ['lt' => date('Y-m-d')])
            ->addFieldToFilter('to_date', ['gt' => date('Y-m-d')])
            ->setOrder('priority', 'ASC')
            ->setOrder('discount_amount', 'DESC');
        $discount_amount = $ruleCollection->setPageSize(1)->getFirstItem()->getDiscountAmount();

        return (float)$discount_amount / 100;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException|Zend_Log_Exception
     */
    public function getRuleId()
    {
        $group_id = $this->_session->getCustomerGroupId();
        $ruleCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_group_ids', ['like' => '%' . $group_id . '%'])
            ->addFieldToFilter('from_date', ['lt' => date('Y-m-d')])
            ->addFieldToFilter('to_date', ['gt' => date('Y-m-d')])
            ->setOrder('priority', 'ASC')
            ->setOrder('discount_amount', 'DESC');

        return $ruleCollection->setPageSize(1)->getFirstItem()->getRuleId();
    }

    /**
     * @return float|int
     * @throws LocalizedException
     * @throws NoSuchEntityException|Zend_Log_Exception
     */
    public function getApplyRuleDiscount($sku, $group_id)
    {
        $ruleCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_group_ids', ['like' => '%' . $group_id . '%'])
            ->addFieldToFilter('from_date', ['lt' => date('Y-m-d')])
            ->addFieldToFilter('to_date', ['gt' => date('Y-m-d')])
            ->setOrder('priority', 'ASC')
            ->setOrder('discount_amount', 'DESC');
        $ruleData = $ruleCollection->setPageSize(1)->getFirstItem()->getProducts();
        $ruleProductDiscount = explode(',', $ruleData);

        foreach ($ruleProductDiscount as $rule) {
            if ($rule == "*" || $rule == "all" || $rule == "All") {
                return $this->getDiscount();
            }
        }

        $ruleCollection = $this->collectionFactory->create()
            ->addFieldToFilter('products', ['like' => '%' . $sku . '%'])
            ->addFieldToFilter('customer_group_ids', ['like' => '%' . $group_id . '%'])
            ->addFieldToFilter('from_date', ['lt' => date('Y-m-d')])
            ->addFieldToFilter('to_date', ['gt' => date('Y-m-d')])
            ->setOrder('priority', 'ASC')
            ->setOrder('discount_amount', 'DESC');
        $discount_amount = $ruleCollection->setPageSize(1)->getFirstItem()->getDiscountAmount();

        return $discount_amount / 100;
    }
}
