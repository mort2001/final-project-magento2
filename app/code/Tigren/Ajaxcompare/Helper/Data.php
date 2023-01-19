<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcompare\Helper;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\Ajaxsuite\Helper\Data as AjaxsuiteHelper;

/**
 * Class Data
 *
 * @package Tigren\Ajaxcompare\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var
     */
    protected $_storeId;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var LayoutFactory
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
     * @var AjaxsuiteHelper
     */
    protected $_ajaxSuite;

    const IS_ENABLE_SUCCESS_HEADER = 'ajaxcompare/general/enabled_success_header';

    const CONTENT_SUCCESS_HEADER = 'ajaxcompare/general/header_success';

    /**
     * Data constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Registry              $coreRegistry
     * @param CustomerSession       $customerSession
     * @param LayoutFactory         $layoutFactory
     * @param EncoderInterface      $jsonEncoder
     * @param DecoderInterface      $jsonDecoder
     * @param AjaxsuiteHelper       $ajaxSuite
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Registry $coreRegistry,
        CustomerSession $customerSession,
        LayoutFactory $layoutFactory,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        AjaxsuiteHelper $ajaxSuite
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSession = $customerSession;
        $this->_layoutFactory = $layoutFactory;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_jsonDecoder = $jsonDecoder;
        $this->_ajaxSuite = $ajaxSuite;

    }

    /**
     * @return string
     */
    public function getAjaxCompareInitOptions()
    {
        $optionsAjaxsuite = $this->_jsonDecoder->decode($this->_ajaxSuite->getAjaxSuiteInitOptions());
        $options = [
            'ajaxCompare' => [
                'enabled' => $this->isEnabledAjaxCompare(),
                'ajaxCompareUrl' => $this->_getUrl('ajaxcompare/compare/addCompare')
            ],
        ];

        return $this->_jsonEncoder->encode(array_merge($optionsAjaxsuite, $options));
    }

    /**
     * @return bool
     */
    public function isEnabledAjaxCompare()
    {
        return (bool)$this->scopeConfig->getValue(
            'ajaxcompare/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getSuccessHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxcompare_success_message')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getErrorHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxcompare_error_message')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @return bool
     */
    public function isEnabledCustomHeaderAjaxCompare()
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
    public function getSuccessHeaderAjaxCompare()
    {
        if ($this->isEnabledCustomHeaderAjaxCompare()) {
            return (string)$this->scopeConfig->getValue(
                self::CONTENT_SUCCESS_HEADER,
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            );
        }

        return null;
    }
}
