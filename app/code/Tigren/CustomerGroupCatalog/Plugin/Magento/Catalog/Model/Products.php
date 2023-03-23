<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Plugin\Magento\Catalog\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tigren\CustomerGroupCatalog\Model\ResourceModel\Rule\CollectionFactory;
use Tigren\CustomerGroupCatalog\Helper\Data;
use Magento\Catalog\Model\Product;

/**
 * Class Products
 * @package Tigren\CustomerGroupCatalog\Plugin\Catalog\Model
 */
class Products
{
    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Data
     */
    protected $_data;

    /**
     * @param Session $session
     * @param CollectionFactory $collectionFactory
     * @param Data $data
     */
    public function __construct(
        Session           $session,
        CollectionFactory $collectionFactory,
        Data              $data
    )
    {
        $this->_session = $session;
        $this->collectionFactory = $collectionFactory;
        $this->_data = $data;
    }

    /**
     * Get product's special price
     *
     * @param Product $product
     * @param float $result
     * @return float|int
     */
    public function afterGetSpecialPrice(Product $product, $result)
    {
        try {
            $group_id = $this->_session->getCustomerGroupId();
            $sku = $product->getSku();
            $discountAmount = $this->_data->getApplyRuleDiscount($sku, $group_id);
            $finalPriceValue = $product->getPriceInfo()->getPrice('final_price')->getValue();

            return $finalPriceValue - ($finalPriceValue * $discountAmount);
        } catch (NoSuchEntityException|LocalizedException) {
        }
    }
}