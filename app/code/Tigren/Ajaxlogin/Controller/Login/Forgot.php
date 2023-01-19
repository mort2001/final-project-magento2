<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Controller\Login;

use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Tigren\Ajaxlogin\Helper\Data;
use Zend_Validate;
use Zend_Validate_Exception;

/**
 * Class Forgot
 *
 * @package Tigren\Ajaxlogin\Controller\Login
 */
class Forgot extends Action
{
    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;
    /**
     * @var Escaper
     */
    protected $escaper;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;
    /**
     * @var Data
     */
    protected $_ajaxLoginHelper;

    /**
     * Forgot constructor.
     *
     * @param Context                    $context
     * @param Session                    $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper                    $escaper
     * @param JsonHelper                 $jsonHelper
     * @param Data                       $ajaxLoginHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        JsonHelper $jsonHelper,
        Data $ajaxLoginHelper
    ) {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper = $escaper;
        $this->jsonHelper = $jsonHelper;
        $this->_ajaxLoginHelper = $ajaxLoginHelper;
        parent::__construct($context);
    }

    /**
     * Forgot customer password action
     *
     * @return void
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function execute()
    {
        $result = [];
        $captchaStatus = $this->session->getResultCaptcha();
        if ($captchaStatus) {
            if (isset($captchaStatus['error'])) {
                $this->session->setResultCaptcha(null);
                $this->getResponse()->setBody($this->jsonHelper->jsonEncode($captchaStatus));
                return;
            }
            $result['imgSrc'] = $captchaStatus['imgSrc'];
        }

        /**
 * @var Redirect $resultRedirect
*/
        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->session->setForgottenEmail($email);
                $result['error'] = __('Please correct the email address.');
            }

            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
                $result['success'] = __(
                    'We have sent a message to your email. Please check your inbox and click on the link to reset your password.'
                );
            } catch (NoSuchEntityException $e) {
                // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
            } catch (SecurityViolationException $exception) {
                $result['error'] = $exception->getMessage();
            } catch (Exception $exception) {
                $result['error'] = __('We\'re unable to send the password reset email.');
            }
        }

        if (!empty($result['error'])) {
            $emailAdmin = 'email@admin.com';
            $htmlPopup = $this->_ajaxLoginHelper->getErrorMessageForgotPasswordPopupHtml($emailAdmin);
            $result['html_popup'] = $htmlPopup;
        } else {
            $htmlPopup = $this->_ajaxLoginHelper->getSuccessMessageForgotPasswordPopupHtml($email);
            $result['html_popup'] = $htmlPopup;
        }
        $this->getResponse()->representJson($this->jsonHelper->jsonEncode($result));
    }
}
