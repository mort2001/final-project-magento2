<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Plugin\Magento\Customer\Controller\Account;

use Magento\Customer\Controller\Account\LoginPost;

/**
 * Class RedirectCustomUrl
 * @package Tigren\CustomerGroupCatalog\Plugin
 */
class RedirectCustomUrl
{
    /**
     * @param LoginPost $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(LoginPost $subject, $result)
    {
        $customUrl = 'tigren_customergroup/rule/index';

        return $result->setPath($customUrl);
    }
}