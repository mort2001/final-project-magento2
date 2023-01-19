<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Helper;

use Magento\Catalog\Helper\Image;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Catalog data helper
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends AbstractHelper
{
    /**
     * Currently selected store ID if applicable
     *
     * @var int
     */
    protected $_storeId;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $_layoutFactory;

    /**
     * @var EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var DecoderInterface
     */
    protected $_jsonDecoder;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Image
     */
    protected $prdImageHelper;

    /**
     * @var \Tigren\Ajaxsuite\Helper\Data
     */
    protected $_ajaxsuiteHelper;

    /**
     * @var StockStateInterface
     */
    protected $_stockState;

    const IS_ENABLE_SUCCESS_HEADER = 'ajaxcart/general/enabled_success_header';

    const CONTENT_SUCCESS_HEADER = 'ajaxcart/general/header_success';

    /**
     * Data constructor.
     *
     * @param Context                       $context
     * @param CustomerSession               $customerSession
     * @param LayoutFactory                 $layoutFactory
     * @param EncoderInterface              $jsonEncoder
     * @param DecoderInterface              $jsonDecoder
     * @param ObjectManagerInterface        $objectManager
     * @param Image                         $imageHelper
     * @param \Tigren\Ajaxsuite\Helper\Data $ajaxsuiteHelper
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        LayoutFactory $layoutFactory,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        ObjectManagerInterface $objectManager,
        Image $imageHelper,
        StockStateInterface $_stockState,
        \Tigren\Ajaxsuite\Helper\Data $ajaxsuiteHelper
    ) {
        $this->_customerSession = $customerSession;
        $this->_layoutFactory = $layoutFactory;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_jsonDecoder = $jsonDecoder;
        $this->_objectManager = $objectManager;
        $this->prdImageHelper = $imageHelper;
        $this->_stockState = $_stockState;
        $this->_ajaxsuiteHelper = $ajaxsuiteHelper;
        parent::__construct($context);
    }

    /**
     * Set a specified store ID value
     *
     * @param  int $store
     * @return $this
     */
    public function setStoreId($store)
    {
        $this->_storeId = $store;
        return $this;
    }

    /**
     * @param  $product
     * @return string
     * @throws LocalizedException
     */
    public function getOptionsPopupHtml($product)
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxcart_options_popup')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @param  $product
     * @return string
     * @throws LocalizedException
     */
    public function getSuccessHtml($product)
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxcart_success_message')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @param  $product
     * @return string
     * @throws LocalizedException
     */
    public function getErrorHtml($product)
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxcart_error_message')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @return bool
     */
    public function isEnabledAjaxcart()
    {
        return (bool)$this->scopeConfig->getValue(
            'ajaxcart/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * @return int
     */
    public function getPopupTTL()
    {
        if ($this->isEnabledPopupTTL()) {
            return (int)$this->scopeConfig->getValue(
                'ajaxcart/general/popupttl',
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            );
        }
        return 0;
    }

    /**
     * @return bool
     */
    public function isEnabledPopupTTL()
    {
        return (bool)$this->scopeConfig->getValue(
            'ajaxcart/general/enabled_popupttl',
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * @return string
     */
    public function getAjaxCartInitOptions()
    {
        $optionsAjaxsuite = $this->_jsonDecoder->decode($this->_ajaxsuiteHelper->getAjaxSuiteInitOptions());
        $options = [
            'ajaxCart' => [
                'addToCartUrl' => $this->_getUrl('ajaxcart/cart/showPopup'),
                'addToCartInWishlistUrl' => $this->_getUrl('ajaxcart/wishlist/showPopup'),
                'checkoutCartUrl' => $this->_getUrl('checkout/cart/add'),
                'wishlistAddToCartUrl' => $this->_getUrl('wishlist/index/cart'),
                'addToCartButtonSelector' => $this->getAddToCartButtonSelector()
            ]
        ];

        return $this->_jsonEncoder->encode(array_merge($optionsAjaxsuite, $options));
    }

    /**
     * @return string
     */
    public function getAddToCartButtonSelector()
    {
        $class = $this->getScopeConfig('ajaxcart/general/addtocart_btn_class');
        if (empty($class)) {
            $class = 'add-to-cart';
        }
        return 'button.' . $class;
    }

    /**
     * @param  $path
     * @return mixed
     */
    public function getScopeConfig($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->_storeId);
    }

    /**
     * @param  $icon
     * @return string
     */
    public function getAjaxSidebarInitOptions($icon)
    {
        $options = [
            'icon' => $icon,
            'texts' => [
                'loaderText' => __('Loading...'),
                'imgAlt' => __('Loading...')
            ]
        ];

        return $this->_jsonEncoder->encode($options);
    }

    /**
     * @param  $price
     * @return int
     */
    public function getPriceWithCurrency($price)
    {
        if ($price) {
            return $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data')->currency($price, true, false);
        }
        return 0;
    }

    /**
     * @param  $product
     * @param  $size
     * @return string
     */
    public function getProductImageUrl($product, $size)
    {
        $imageSize = 'product_page_image_' . $size;
        if ($size === 'category') {
            $imageSize = 'category_page_list';
        }
        return $this->prdImageHelper->init($product, $imageSize)
            ->keepAspectRatio(true)
            ->keepFrame(false)
            ->getUrl();
    }

    /**
     * @return mixed
     */
    public function getEnableAjaxShoppingCart()
    {
        return $this->scopeConfig->getValue(
            'ajaxcart/general/ajax_update_cart_page',
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * @return mixed
     */
    public function getEnableAjaxLoadQty()
    {
        return $this->scopeConfig->getValue(
            'ajaxcart/general/ajax_update_qty',
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * @param  $product
     * @return float
     */
    public function getStockState($product)
    {
        return $this->_stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
    }

    /**
     * @return bool
     */
    public function isEnabledCustomHeaderAjaxCart()
    {
        return (bool)$this->scopeConfig->getValue(
            self::IS_ENABLE_SUCCESS_HEADER,
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * @return string|null
     */
    public function getSuccessHeaderAjaxCart()
    {
        if ($this->isEnabledCustomHeaderAjaxCart()) {
            return (string)$this->scopeConfig->getValue(
                self::CONTENT_SUCCESS_HEADER,
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            );
        }

        return null;
    }
}
