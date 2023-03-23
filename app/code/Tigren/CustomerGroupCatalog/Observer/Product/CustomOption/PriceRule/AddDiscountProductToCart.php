<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Observer\Product\CustomOption\PriceRule;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\CustomerGroupCatalog\Helper\Data;
use Tigren\CustomerGroupCatalog\Model\ResourceModel\Rule\CollectionFactory;
use Zend_Log_Exception;

/**
 * Class Cartload
 * @package Vendor\Module\Observer\Product\CustomOption\PriceRule
 */
class AddDiscountProductToCart implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_data;

    /**
     * @var ManagerInterface
     */
    protected $_message;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @param Product $product
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param Session $session
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $_message
     * @param Data $data
     */
    public function __construct(
        Product               $product,
        StoreManagerInterface $storeManager,
        RequestInterface      $request,
        Session               $session,
        CollectionFactory     $collectionFactory,
        ManagerInterface      $_message,
        Data                  $data
    ) {
        $this->_data = $data;
        $this->_message = $_message;
        $this->collectionFactory = $collectionFactory;
        $this->_session = $session;
        $this->_product = $product;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
    }

    /**
     * @param Observer $observer
     * @throws NoSuchEntityException|LocalizedException|Zend_Log_Exception|
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getData('product');
        $sku = $product->getSku();
        $group_id = $this->_session->getCustomerGroupId();
        $discount_amount = $this->_data->getApplyRuleDiscount($sku, $group_id);

        $item = $observer->getEvent()->getData('quote_item');
        $item = ($item->getParentItem() ? $item->getParentItem() : $item);
        $productCollection = $this->_product->loadByAttribute('sku', $sku);
        $productPriceBySku = $productCollection->getPrice();
        $customPrice = $productPriceBySku - ($productPriceBySku * $discount_amount); // custom price
        $item->setCustomPrice($customPrice);
        $item->setOriginalCustomPrice($customPrice);
        $item->getProduct()->setIsSuperMode(true);
    }
}
