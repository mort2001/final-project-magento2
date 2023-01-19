<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Helper;

use Exception;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Catalog data helper
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var
     */
    protected $customerFactory;

    /**
     * @var
     */
    protected $objectManager;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var
     */
    protected $_urlBuilder;

    /**
     * @var EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var DecoderInterface
     */
    protected $_jsonDecoder;

    /**
     * @var LayoutFactory
     */
    protected $_layoutFactory;

    /**
     * @var \Tigren\Ajaxsuite\Helper\Data
     */
    protected $_ajaxSuiteHelper;

    const IS_ENABLE_SUCCESS_HEADER = 'ajaxlogin/general/enabled_success_header';

    const CONTENT_SUCCESS_HEADER = 'ajaxlogin/general/header_success';

    /**
     * Data constructor.
     *
     * @param  Context                       $context
     * @param  StoreManagerInterface         $storeManager
     * @param  ObjectManagerInterface        $objectManager
     * @param  CustomerFactory               $customerFactory
     * @param  Registry                      $coreRegistry
     * @param  CustomerSession               $customerSession
     * @param  LayoutFactory                 $layoutFactory
     * @param  EncoderInterface              $jsonEncoder
     * @param  DecoderInterface              $jsonDecoder
     * @param  \Tigren\Ajaxsuite\Helper\Data $ajaxSuiteHelper
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager,
        CustomerFactory $customerFactory,
        Registry $coreRegistry,
        CustomerSession $customerSession,
        LayoutFactory $layoutFactory,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        \Tigren\Ajaxsuite\Helper\Data $ajaxSuiteHelper
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectManager;
        $this->_customerFactory = $customerFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSession = $customerSession;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_jsonDecoder = $jsonDecoder;
        $this->_layoutFactory = $layoutFactory;
        $this->_ajaxSuiteHelper = $ajaxSuiteHelper;
        $this->setStoreId($this->getCurrentStoreId());
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
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }

    /**
     * Get Login Popup
     *
     * @return string
     * @throws LocalizedException
     */
    public function getLoginPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_login_popup')->load();
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
    public function getSuccessMessageLoginPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_login_success')->load();
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
    public function getSuccessMessageRegisterPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_register_success')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @param  $email
     * @return string
     * @throws LocalizedException
     */
    public function getSuccessMessageForgotPasswordPopupHtml($email)
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_forgotpassword_success')->load();
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
    public function getSuccessMessageLogoutPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_logout_success')->load();
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
    public function getErrorMessageLoginPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_login_error')->load();
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
    public function getErrorMessageRegisterPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_register_error')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @param  $email
     * @return string
     * @throws LocalizedException
     */
    public function getErrorMessageForgotPasswordPopupHtml($email)
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_forgotpassword_error')->load();
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
    public function getErrorMessageLogoutPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_logout_error')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * Get Register Popup
     *
     * @return string
     * @throws LocalizedException
     */
    public function getRegisterPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_register_popup')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * Get Forgot Password Popup
     *
     * @return string
     * @throws LocalizedException
     */
    public function getForgotPasswordPopupHtml()
    {
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle('ajaxlogin_forgotpassword_popup')->load();
        $layout->generateXml();
        $layout->generateElements();
        $result = $layout->getOutput();
        $layout->__destruct();
        return $result;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAjaxLoginInitOptions()
    {
        $optionsAjaxsuite = $this->_jsonDecoder->decode($this->_ajaxSuiteHelper->getAjaxSuiteInitOptions());
        $options = [
            'ajaxLogin' => [
                'ajaxGetPopupUrl' => $this->_getUrl('ajaxsuite/login/showPopup'),
                'ajaxLoginUrl' => $this->_getUrl('ajaxlogin/login/login'),
                'ajaxSocialLoginUrl' => $this->_getUrl('ajaxlogin/login/socialLogin'),
                'ajaxRegisterUrl' => $this->_getUrl('ajaxlogin/login/create'),
                'ajaxTwitterUrl' => $this->_getUrl('ajaxlogin/login/twitter'),
                'ajaxForgotPasswordUrl' => $this->_getUrl('ajaxlogin/login/forgot'),
                'ajaxLogoutUrl' => $this->_getUrl('ajaxlogin/login/logout'),
                'enabled' => $this->getScopeConfig('ajaxlogin/general/enabled'),
                'urlRedirect' => $this->getScopeConfig('ajaxlogin/general/login_destination'),
                'slideAnimation' => $this->getScopeConfig('ajaxlogin/general/slide_animation'),
                'socialLoginEnable' => $this->getScopeConfig('ajaxlogin/social_login/enable'),
                'facebookAppId' => $this->getScopeConfig('ajaxlogin/social_login/facebook_appid'),
                'ggPlusClientId' => $this->getScopeConfig('ajaxlogin/social_login/googleplus_clientid'),
                'baseUrl' => $this->getBaseUrl()
            ]
        ];
        $options = array_merge($optionsAjaxsuite, $options);

        return $this->_jsonEncoder->encode($options);
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
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->_customerSession->isLoggedIn();
    }

    /**
     * @return mixed
     */
    public function getTwitterConsumerKey()
    {
        return $this->getScopeConfig('ajaxlogin/social_login/twitter_consumer_key');
    }

    /**
     * @return mixed
     */
    public function getTwitterConsumerSecret()
    {
        return $this->getScopeConfig('ajaxlogin/social_login/twitter_consumer_secret');
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getTwitterLoginUrl()
    {
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . 'ajaxlogin/login/twitter.php';
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getTwitterCallbackUrl()
    {
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . 'ajaxlogin/login/callback';
    }

    /**
     * @param  $email
     * @param  null  $websiteId
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getCustomerByEmail($email, $websiteId = null)
    {
        $customer = $this->_objectManager->get(
            'Magento\Customer\Model\Customer'
        );
        if (!$websiteId) {
            $customer->setWebsiteId($this->_storeManager->getWebsite()->getId());
        } else {
            $customer->setWebsiteId($websiteId);
        }
        $customer->loadByEmail($email);

        if ($customer->getId()) {
            return $customer;
        }
        return false;
    }

    /**
     * @param  $data
     * @param  $websiteId
     * @param  $storeId
     * @return Customer
     */
    public function createCustomerMultiWebsite($data, $websiteId, $storeId)
    {
        $customer = $this->_customerFactory->create();
        $customer->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setEmail($data['email'])
            ->setWebsiteId($websiteId)
            ->setStoreId($storeId);
        try {
            $customer->save();
        } catch (Exception $e) {
        }

        return $customer;
    }

    /**
     * @return bool
     */
    public function isEnabledCustomHeaderAjaxLogin()
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
    public function getSuccessHeaderAjaxLogin()
    {
        if ($this->isEnabledCustomHeaderAjaxLogin()) {
            return (string)$this->scopeConfig->getValue(
                self::CONTENT_SUCCESS_HEADER,
                ScopeInterface::SCOPE_STORE,
                $this->_storeId
            );
        }

        return null;
    }

}
