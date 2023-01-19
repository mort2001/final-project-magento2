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
use Magento\Store\Model\StoreManagerInterface;
use Tigren\Ajaxlogin\Helper\Data;
use Tigren\Ajaxlogin\Helper\TwitterOAuth\TwitterOAuth;
use Tigren\Ajaxlogin\Helper\TwitterOAuth\TwitterOAuthException;

/**
 * Class Callback
 *
 * @package Tigren\Ajaxlogin\Controller\Login
 */
class Callback extends Action
{
    /**
     * @var Data
     */
    protected $_ajaxLoginHelper;
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
     * Callback constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Registration          $registration
     * @param Session               $customerSession
     * @param Data                  $ajaxLoginHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Registration $registration,
        Session $customerSession,
        Data $ajaxLoginHelper
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->registration = $registration;
        $this->customerSession = $customerSession;
        $this->_ajaxLoginHelper = $ajaxLoginHelper;
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws TwitterOAuthException
     */
    public function execute()
    {
        $consumerKey = $this->_ajaxLoginHelper->getTwitterConsumerKey();
        $consumerSecret = $this->_ajaxLoginHelper->getTwitterConsumerSecret();
        $loginUrl = $this->_ajaxLoginHelper->getTwitterLoginUrl();

        // get and filter oauth verifier
        $oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier');

        // check tokens
        if (empty($oauth_verifier)
            || empty($_SESSION['oauth_token'])
            || empty($_SESSION['oauth_token_secret'])
        ) {
            // something's missing, go and login again
            header('Location: ' . $loginUrl);
        }

        // connect with application token
        $connection = new TwitterOAuth(
            $consumerKey,
            $consumerSecret,
            $_SESSION['oauth_token'],
            $_SESSION['oauth_token_secret']
        );

        // request user token
        $token = $connection->oauth(
            'oauth/access_token',
            [
                'oauth_verifier' => $oauth_verifier
            ]
        );

        // connect with user token
        $twitter = new TwitterOAuth(
            $consumerKey,
            $consumerSecret,
            $token['oauth_token'],
            $token['oauth_token_secret']
        );

        $params = ['include_email' => 'true', 'include_entities' => 'false', 'skip_status' => 'true'];

        $user = $twitter->get('account/verify_credentials', $params);
        $tmp = json_decode(json_encode($user), true);
        $params = [
            'firstname' => $tmp['name'],
            'lastname' => $tmp['screen_name'],
            'email' => $tmp['email'],
            'social_type' => 'twitter',
            'password' => 'Tigren123!'
        ];

        $result = [];
        if (!$this->registration->isAllowed()) {
            $result['error'] = __('Registration is not allow.');
        } else {
            if ($this->customerSession->isLoggedIn()) {
                $result['error'] = __('You have already logged in.');
            } else {
                $this->customerSession->regenerateId();
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
            echo $result['error'];
        } else {
            echo __('You have logged in with twitter succesfully.');
            $this->redirect();
        }

        return;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function redirect()
    {
        echo "<script language='javascript' type='text/javascript'>";
        echo "window.close();";
        $urlRedirect = $this->_ajaxLoginHelper->getScopeConfig('ajaxlogin/general/login_destination');
        $baseUrl = $this->_ajaxLoginHelper->getBaseUrl();
        $customerPageUrl = $baseUrl . 'customer/account';
        $cartUrl = $baseUrl . 'checkout/cart';
        $wishlistUrl = $baseUrl . 'wishlist';
        if ($urlRedirect == 0) {
            echo "window.opener.location.reload();";
        } else {
            if ($urlRedirect == 1) {
                echo "window.opener.location.replace('" . $customerPageUrl . "');";
            } else {
                if ($urlRedirect == 2) {
                    echo "window.opener.location.replace('" . $baseUrl . "');";
                } else {
                    if ($urlRedirect == 3) {
                        echo "window.opener.location.replace('" . $cartUrl . "');";
                    } else {
                        if ($urlRedirect == 4) {
                            echo "window.opener.location.replace('" . $wishlistUrl . "');";
                        } else {
                            echo "window.opener.location.replace('" . $customerPageUrl . "');";
                        }
                    }
                }
            }
        }
        echo "</script>";
    }
}
