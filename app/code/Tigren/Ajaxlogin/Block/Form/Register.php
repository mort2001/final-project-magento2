<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Block\Form;

/**
 * Class Register
 *
 * @package Tigren\Ajaxlogin\Block\Form
 */
class Register extends \Magento\Customer\Block\Form\Register
{
    /**
     * Get login URL
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->_customerUrl->getLoginUrl();
    }
}
