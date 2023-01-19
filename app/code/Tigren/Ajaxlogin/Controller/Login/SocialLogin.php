<?php

/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Controller\Login;

use Magento\Customer\Model\Registration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\Ajaxlogin\Helper\Data as AjaxLoginHelper;

/**
 * Class SocialLogin
 *
 * @package Tigren\Ajaxlogin\Controller\Login
 */
class SocialLogin extends Action
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Registration
     */
    protected $registration;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;
    /**
     * @var AjaxLoginHelper
     */
    protected $_ajaxLoginHelper;

    /**
     * SocialLogin constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Registration          $registration
     * @param Session               $customerSession
     * @param JsonHelper            $jsonHelper
     * @param AjaxLoginHelper       $ajaxLoginHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Registration $registration,
        Session $customerSession,
        JsonHelper $jsonHelper,
        AjaxLoginHelper $ajaxLoginHelper
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->registration = $registration;
        $this->customerSession = $customerSession;
        $this->jsonHelper = $jsonHelper;
        $this->_ajaxLoginHelper = $ajaxLoginHelper;
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = [];
        if (!$this->registration->isAllowed()) {
            $result['error'] = __('Registration is not allow.');
        } else {
            if ($this->customerSession->isLoggedIn()) {
                $result['error'] = __('You have already logged in.');
            } else {
                $this->customerSession->regenerateId();
                $params = $this->getRequest()->getPost();
                $socialType = $params['social_type'];
                if ($params) {
                    $storeId = $this->storeManager->getStore()->getStoreId();
                    $websiteId = $this->storeManager->getStore()->getWebsiteId();
                    $data = [
                        'firstname' => $params['firstname'],
                        'lastname' => $params['lastname'],
                        'email' => $params['email'],
                        'password' => $params['password']
                    ];
                    if ($data['email']) {
                        $customer = $this->_ajaxLoginHelper->getCustomerByEmail($data['email'], $websiteId);
                        if (!$customer || !$customer->getId()) {
                            $customer = $this->_ajaxLoginHelper->createCustomerMultiWebsite(
                                $data,
                                $websiteId,
                                $storeId
                            );
                            if ($this->_ajaxLoginHelper->getScopeConfig('ajaxlogin/social_login/send_pass')) {
                                $customer->sendPasswordReminderEmail();
                            }
                        }
                        $this->customerSession->setCustomerAsLoggedIn($customer);
                    } else {
                        $result['error'] = __('Something wrong with getting your email of your ') . $socialType;
                    }
                } else {
                    $result['error'] = __('Something wrong when processing Ajax.');
                }
            }
        }

        if (!empty($result['error'])) {
            $htmlPopup = $this->_ajaxLoginHelper->getErrorMessageLoginPopupHtml();
            $result['html_popup'] = $htmlPopup;
        } else {
            $htmlPopup = $this->_ajaxLoginHelper->getSuccessMessageLoginPopupHtml();
            $result['html_popup'] = $htmlPopup;
        }
        return $this->getResponse()->representJson($this->jsonHelper->jsonEncode($result));
    }
}
