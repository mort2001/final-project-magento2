<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Controller\Wishlist;

use Exception;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Wishlist\Controller\AbstractIndex;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Item\OptionFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\LocaleQuantityProcessor;
use Tigren\Ajaxcart\Helper\Data;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShowPopup extends AbstractIndex
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var LocaleQuantityProcessor
     */
    protected $quantityProcessor;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var Cart
     */
    protected $cartHelper;
    /**
     * @var Product
     */
    protected $productHelper;
    /**
     * @var Escaper
     */
    protected $escaper;
    /**
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $helper;
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var Data
     */
    protected $_ajaxcartData;
    /**
     * @var OptionFactory
     */
    private $optionFactory;

    /**
     * @param Action\Context $context
     * @param WishlistProviderInterface $wishlistProvider
     * @param LocaleQuantityProcessor $quantityProcessor
     * @param ItemFactory $itemFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param OptionFactory $optionFactory
     * @param Product $productHelper
     * @param Escaper $escaper
     * @param \Magento\Wishlist\Helper\Data $helper
     * @param Cart $cartHelper
     * @param Registry $registry
     * @param Data $ajaxcartData
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        WishlistProviderInterface $wishlistProvider,
        LocaleQuantityProcessor $quantityProcessor,
        ItemFactory $itemFactory,
        \Magento\Checkout\Model\Cart $cart,
        OptionFactory $optionFactory,
        Product $productHelper,
        Escaper $escaper,
        \Magento\Wishlist\Helper\Data $helper,
        Cart $cartHelper,
        Registry $registry,
        Data $ajaxcartData
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->quantityProcessor = $quantityProcessor;
        $this->itemFactory = $itemFactory;
        $this->cart = $cart;
        $this->optionFactory = $optionFactory;
        $this->productHelper = $productHelper;
        $this->escaper = $escaper;
        $this->helper = $helper;
        $this->cartHelper = $cartHelper;
        $this->_ajaxcartData = $ajaxcartData;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Add product to shopping cart from wishlist action
     *
     * @return                                       void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        try {
            $itemId = (int)$params['item'];

            /**
             * @var Redirect $resultRedirect
             */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            /* @var $item Item */
            $item = $this->itemFactory->create()->load($itemId);
            $product = $item->getProduct();

            if (!empty($params['ajaxcart_error'])) {
                $this->_coreRegistry->register('product', $product);
                $this->_coreRegistry->register('current_product', $product);

                $htmlPopup = $this->_ajaxcartData->getErrorHtml($product);
                $result['error'] = true;
                $result['html_popup'] = $htmlPopup;
                $result['item'] = $itemId;

                $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                );

                return;
            }

            if (!$item->getId()) {
                $resultRedirect->setPath('*/*');
                return $resultRedirect;
            }
            $wishlist = $this->wishlistProvider->getWishlist($item->getWishlistId());
            if (!$wishlist) {
                $resultRedirect->setPath('*/*');
                return $resultRedirect;
            }

            if (!$product) {
                $resultRedirect->setPath('*/*');
                return $resultRedirect;
            }

            if (!empty($params['ajaxcart_success'])) {
                $item->delete();
                $wishlist->save();

                $this->_coreRegistry->register('product', $product);
                $this->_coreRegistry->register('current_product', $product);
                $htmlPopup = $this->_ajaxcartData->getSuccessHtml($product);
                $result['success'] = true;
                $result['html_popup'] = $htmlPopup;
                $result['item'] = $itemId;

                $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                );

                return;
            }

            /* return options popup content when product type is grouped */
            if ($product->getHasOptions()
                || ($product->getTypeId() == 'grouped' && !isset($params['super_group']))
                || ($product->getTypeId() == 'configurable' && !isset($params['super_attribute']))
                || $product->getTypeId() == 'bundle'
            ) {
                $options = $this->optionFactory->create()->getCollection()->addItemFilter([$itemId]);
                $item->setOptions($options->getOptionsByItem($itemId));

                $buyRequest = $this->productHelper->addParamsToBuyRequest(
                    $this->getRequest()->getParams(),
                    ['current_config' => $item->getBuyRequest()]
                );

                if ($params['has_options_detail']) {
                    $item->mergeBuyRequest($buyRequest);
                    $item->addToCart($this->cart, true);
                    $this->cart->save()->getQuote()->collectTotals();
                    $item->delete();
                    $wishlist->save();

                    $this->_coreRegistry->register('product', $product);
                    $this->_coreRegistry->register('current_product', $product);
                    $htmlPopup = $this->_ajaxcartData->getSuccessHtml($product);
                    $result['success'] = true;
                    $result['html_popup'] = $htmlPopup;
                    $result['item'] = $itemId;
                    $result['addto'] = true;

                    $this->getResponse()->representJson(
                        $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                    );

                    return;
                } else {
                    if ($product->getTypeId() === 'bundle') {
                        $result['product_url'] = $product->getProductUrl();

                        return $this->getResponse()->representJson(
                            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                        );
                    }

                    $this->_coreRegistry->register('product', $product);
                    $this->_coreRegistry->register('current_product', $product);

                    $htmlPopup = $this->_ajaxcartData->getOptionsPopupHtml($product);
                    $result['success'] = true;
                    $result['html_popup'] = $htmlPopup;
                    $result['item'] = $itemId;

                    $this->getResponse()->representJson(
                        $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                    );

                    return;
                }
            } else {
                $params['product'] = $product->getId();

                $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($params)
                );

                $this->_forward(
                    'add',
                    'cart',
                    'checkout',
                    $params
                );

                return;
            }
        } catch (ProductException $e) {
            $this->messageManager->addError(__('This product(s) is out of stock.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addNotice($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add the item to the cart right now.'));
        }

        $this->helper->calculate();
    }
}
