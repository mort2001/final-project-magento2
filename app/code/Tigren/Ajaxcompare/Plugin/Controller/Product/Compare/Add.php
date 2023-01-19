<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcompare\Plugin\Controller\Product\Compare;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\Ajaxsuite\Helper\Data as AjaxgroupData;

/**
 * Class Add
 *
 * @package Tigren\Ajaxcompare\Plugin\Controller\Product\Compare
 */
class Add
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var null
     */
    protected $_coreRegistry = null;

    /**
     * @var AjaxgroupData Data
     */
    protected $_ajaxCompareHelper;

    /**
     * @var Data
     */
    protected $_jsonEncode;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var RedirectFactory
     */
    protected $_resultRedirectFactory;

    /**
     * Add constructor.
     *
     * @param StoreManagerInterface           $storeManager
     * @param Registry                        $registry
     * @param AjaxgroupData                   $ajaxSuiteHelper
     * @param Data                            $jsonEncode
     * @param ProductRepositoryInterface      $productRepository
     * @param RedirectFactory                 $redirectFactory
     * @param \Tigren\Ajaxcompare\Helper\Data $ajaxCompareHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Registry $registry,
        AjaxgroupData $ajaxSuiteHelper,
        Data $jsonEncode,
        ProductRepositoryInterface $productRepository,
        RedirectFactory $redirectFactory,
        \Tigren\Ajaxcompare\Helper\Data $ajaxCompareHelper
    ) {
        $this->_resultRedirectFactory = $redirectFactory;
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $registry;
        $this->_ajaxSuiteHelper = $ajaxSuiteHelper;
        $this->_jsonEncode = $jsonEncode;
        $this->_productRepository = $productRepository;
        $this->_ajaxCompareHelper = $ajaxCompareHelper;
    }

    /**
     * Init popup ajax compare
     *
     * @param  \Magento\Catalog\Controller\Product\Compare\Add $subject
     * @param  $proceed
     * @return Redirect
     * @throws NoSuchEntityException
     */
    public function aroundExecute(\Magento\Catalog\Controller\Product\Compare\Add $subject, $proceed)
    {
        $result = [];
        $params = $subject->getRequest()->getParams();
        $productId = $params['product'];

        $product = $this->_initProduct($productId);

        if (!empty($params['isCompare'])) {
            $proceed();
            $this->_coreRegistry->register('product', $product);
            $this->_coreRegistry->register('current_product', $product);

            $htmlPopup = $this->_ajaxCompareHelper->getOptionsPopupHtml($product, 'isCompare');
            $result['success'] = true;
            $result['html_popup'] = $htmlPopup;

            $subject->getResponse()->representJson($this->_jsonEncode->jsonEncode($result));
        } else {
            $proceed();
            return $this->_resultRedirectFactory->create()->setPath('/*/*');
        }
    }

    /**
     * @param  $productId
     * @return bool|ProductInterface
     * @throws NoSuchEntityException
     */
    protected function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                $product = $this->_productRepository->getById($productId, false, $storeId);

                return $product;
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }
}
