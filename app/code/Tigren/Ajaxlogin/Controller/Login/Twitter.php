<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Controller\Login;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Tigren\Ajaxlogin\Helper\Data;
use Tigren\Ajaxlogin\Helper\TwitterOAuth\TwitterOAuth;
use Tigren\Ajaxlogin\Helper\TwitterOAuth\TwitterOAuthException;

/**
 * Class Twitter
 *
 * @package Tigren\Ajaxlogin\Controller\Login
 */
class Twitter extends Action
{
    /**
     * @var Data
     */
    protected $_ajaxLoginHelper;
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * Twitter constructor.
     *
     * @param Context    $context
     * @param Data       $ajaxLoginHelper
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Context $context,
        Data $ajaxLoginHelper,
        JsonHelper $jsonHelper
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_ajaxLoginHelper = $ajaxLoginHelper;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws NoSuchEntityException
     * @throws TwitterOAuthException
     */
    public function execute()
    {
        $result = [];
        $consumerKey = $this->_ajaxLoginHelper->getTwitterConsumerKey();
        $consumerSecret = $this->_ajaxLoginHelper->getTwitterConsumerSecret();
        $callbackUrl = $this->_ajaxLoginHelper->getTwitterCallbackUrl();

        // create TwitterOAuth object
        $twitteroauth = new TwitterOAuth($consumerKey, $consumerSecret);

        // request token of application
        $request_token = $twitteroauth->oauth(
            'oauth/request_token',
            [
                'oauth_callback' => $callbackUrl
            ]
        );

        // throw exception if something gone wrong
        if ($twitteroauth->getLastHttpCode() != 200) {
            $result['error'] = __('There was a problem performing this request');
        }

        // save token of application to session
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

        // generate the URL to make request to authorize our application
        $url = $twitteroauth->url(
            'oauth/authorize',
            [
                'oauth_token' => $request_token['oauth_token']
            ]
        );

        $result['success'] = true;
        $result['url'] = $url;

        return $this->getResponse()->representJson($this->jsonHelper->jsonEncode($result));
    }
}
