<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Controller\Rule;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Tigren\CustomerGroupCatalog\Helper\Data;

/**
 * Class Index
 * @package Tigren\CustomerGroupCatalog\Controller\Rule
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var Data
     */
    protected $_discount;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Data $discount
     */
    public function __construct(
        Context     $context,
        PageFactory $pageFactory,
        Data        $discount
    )
    {
        $this->_discount = $discount;
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * @return Page|ResultInterface
     */
    public function execute()
    {
        return $this->_pageFactory->create();
    }
}