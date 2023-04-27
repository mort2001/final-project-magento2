<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\AdvancedCheckout\Controller\Adminhtml\Payment;

use Magento\Backend\Model\View\Result\Page;
use Tigren\CustomerGroupCatalog\Controller\Adminhtml\Rule;

/**
 * Class Index
 * @package Tigren\CustomerGroupCatalog\Controller\Adminhtml\Rule
 */
class History extends Rule
{
    /**
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Tigren Advanced Checkout - Placed Order History'));

        return $resultPage;
    }
}