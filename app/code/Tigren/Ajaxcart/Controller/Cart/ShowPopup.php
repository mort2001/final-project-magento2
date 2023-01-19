<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Controller\Cart;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Controller\Cart\Add;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\Ajaxcart\Helper\Data as AjaxCartData;
use Zend_Filter_LocalizedToNormalized;

/**
 * Class ShowPopup
 * @package Tigren\Ajaxcart\Controller\Cart
 */
class ShowPopup extends Add
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var AjaxCartData Cart Data
     */
    protected $_ajaxCartData;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param Registry $registry
     * @param AjaxCartData $ajaxcartData
     * @codeCoverageIgnore
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        Registry $registry,
        AjaxCartData $ajaxcartData
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
        $this->_ajaxCartData = $ajaxcartData;
        $this->_coreRegistry = $registry;
    }

    /**
     * Add product to shopping cart action
     *
     * @return ResponseInterface|Redirect|ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    [
                        'locale' => $this->_objectManager->get(
                            ResolverInterface::class
                        )->getLocale()
                    ]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
            }

            if (!empty($params['ajaxcart_error'])) {
                $this->_coreRegistry->register('product', $product);
                $this->_coreRegistry->register('current_product', $product);

                $htmlPopup = $this->_ajaxCartData->getErrorHtml($product);
                $result['error'] = true;
                $result['html_popup'] = $htmlPopup;

                return $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                );
            }

            if (!empty($params['ajaxcart_success'])) {
                $this->_coreRegistry->register('product', $product);
                $this->_coreRegistry->register('current_product', $product);

                $htmlPopup = $this->_ajaxCartData->getSuccessHtml($product);
                $result['success'] = true;
                $result['html_popup'] = $htmlPopup;

                return $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                );
            }

            $isEmptySuperAttribute = false;
            if (empty($params['super_attribute'])) {
                $isEmptySuperAttribute = true;
            } elseif ($product->getTypeId() === 'configurable') {
                foreach ($params['super_attribute'] as $superAttributeValue) {
                    if (!$superAttributeValue) {
                        $isEmptySuperAttribute = true;
                        break;
                    }
                }
            }

            $isEmptySuperGroup = false;
            if (empty($params['super_group'])) {
                $isEmptySuperGroup = true;
            } elseif ($product->getTypeId() === 'grouped') {
                foreach ($params['super_group'] as $superGroupValue) {
                    if (!$superGroupValue) {
                        $isEmptySuperGroup = true;
                        break;
                    }
                }
            }

            /* return options popup content when product type is grouped */
            if ($product->getHasOptions()
                || ($product->getTypeId() === 'grouped' && $isEmptySuperGroup)
                || ($product->getTypeId() === 'configurable' && $isEmptySuperAttribute)
                || $product->getTypeId() === 'bundle'
                || $product->getTypeId() === 'downloadable'
            ) {
                $this->_coreRegistry->register('product', $product);
                $this->_coreRegistry->register('current_product', $product);

                $htmlPopup = $this->_ajaxCartData->getOptionsPopupHtml($product);
                $result['success'] = true;
                $result['html_popup'] = $htmlPopup;

                if ($product->getTypeId() === 'configurable') {
                    $count = 0;
                    foreach ($params['super_attribute'] as $param) {
                        if ($param) {
                            $count++;
                        }
                    }
                    $configurableAttributes = $product->getTypeInstance()->getConfigurableAttributes($product);
                    if ($count === $configurableAttributes->getSize()) {
                        $this->_forward('add', 'cart', 'checkout', $params);
                    } else {
                        return $this->getResponse()->representJson(
                            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                        );
                    }
                } else {
                    return $this->getResponse()->representJson(
                        $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
                    );
                }
            } else {
                $this->_forward('add', 'cart', 'checkout', $params);
            }
        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);

            if (!$url) {
                $cartUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
                $url = $this->_redirect->getRedirectUrl($cartUrl);
            }

            return $this->goBack($url);
        } catch (Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            return $this->goBack();
        }

        return $this->goBack();
    }
}
