<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Block\Form;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Ajaxlogin\Helper\Data;

/**
 * Class Login
 *
 * @package Tigren\Ajaxlogin\Block\Form
 */
class Login extends \Magento\Customer\Block\Form\Login
{

    /**
     * @var Data
     */
    protected $_ajaxloginHelper;

    /**
     * Login constructor.
     *
     * @param Context $context
     * @param Session $customerSession
     * @param Url     $customerUrl
     * @param Data    $ajaxloginHelper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Url $customerUrl,
        Data $ajaxloginHelper,
        array $data
    ) {
        parent::__construct($context, $customerSession, $customerUrl, $data);
        $this->_ajaxloginHelper = $ajaxloginHelper;
    }

    /**
     * @return mixed
     */
    public function isEnableSocialLogin()
    {
        return $this->_ajaxloginHelper->getScopeConfig('ajaxlogin/social_login/enable');
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->_ajaxloginHelper->isLoggedIn();
    }
}
