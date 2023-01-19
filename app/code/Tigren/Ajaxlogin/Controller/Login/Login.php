<?php

/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Controller\Login;

use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Tigren\Ajaxlogin\Helper\Data;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Exception\AuthenticationException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Login extends Action
{

    /**
     * @var
     */
    protected $helperData;
    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;
    /**
     * @var Data
     */
    protected $_ajaxLoginHelper;
    /**
     * @var
     */
    private $cookieMetadataManager;
    /**
     * @var
     */
    private $cookieMetadataFactory;
    /**
     * @var
     */
    private $scopeConfig;

    /**
     * Login constructor.
     *
     * @param Context                    $context
     * @param AccountManagementInterface $accountManagement
     * @param CustomerSession            $customerSession
     * @param JsonHelper                 $jsonHelper
     * @param Data                       $ajaxloginHelper
     */
    public function __construct(
        Context $context,
        AccountManagementInterface $accountManagement,
        CustomerSession $customerSession,
        JsonHelper $jsonHelper,
        Data $ajaxloginHelper
    ) {
        $this->_ajaxLoginHelper = $ajaxloginHelper;
        parent::__construct($context);
        $this->accountManagement = $accountManagement;
        $this->customerSession = $customerSession;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws LocalizedException
     */
    public function execute()
    {
        $result = [];
        if ($this->customerSession->isLoggedIn()) {
            $result['error'] = __('You have already logged in.');
        } else {
            $login = $this->getRequest()->getParams();
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->accountManagement->authenticate($login['username'], $login['password']);
                    $this->customerSession->setCustomerDataAsLoggedIn($customer);
                    $this->customerSession->regenerateId();
                    $result['success'] = __('You have logged in successfully. Please wait ...');
                    if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                        $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                        $metadata->setPath('/');
                        $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                    }
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $result['error'] = __(
                        'This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.',
                        $value
                    );
                } catch (UserLockedException $e) {
                    $result['error'] = __(
                        'The account is locked. Please wait and try again or contact %1.',
                        $this->getScopeConfig()->getValue('contact/email/recipient_email')
                    );
                } catch (AuthenticationException $e) {
                    $result['error'] = __('Invalid login or password.');
                } catch (LocalizedException $e) {
                    $result['error'] = $e->getMessage();
                } catch (Exception $e) {
                    // PA DSS violation: throwing or logging an exception here can disclose customer password
                    $result['error'] = $e->getMessage();
                    //                    $result['error'] = __('An unspecified error occurred. Please contact us for assistance.');
                }
            } else {
                $result['error'] = 'A login and a password are required.';
            }
        }

        if (!empty($result['error'])) {
            $htmlPopup = $this->_ajaxLoginHelper->getErrorMessageLoginPopupHtml();
            $result['html_popup'] = $htmlPopup;
        } else {
            $htmlPopup = $this->_ajaxLoginHelper->getSuccessMessageLoginPopupHtml();
            $result['html_popup'] = $htmlPopup;
        }
        $this->getResponse()->representJson($this->jsonHelper->jsonEncode($result));
    }

    /**
     * Retrieve cookie manager
     *
     * @return     PhpCookieManager
     * @deprecated
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(
                PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @return     CookieMetadataFactory
     * @deprecated
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(
                CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * Get scope config
     *
     * @return     ScopeConfigInterface
     * @deprecated
     */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof ScopeConfigInterface)) {
            return ObjectManager::getInstance()->get(
                ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }
}
