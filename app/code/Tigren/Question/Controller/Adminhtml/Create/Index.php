<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Controller\Adminhtml\Create;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Tigren\Question\Controller\Adminhtml\Create
 */
class Index extends Action
{
    /**
     * @var bool|PageFactory
     */
    protected $_pageFactory = false;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context     $context,
        PageFactory $pageFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * @return ResultInterface|Page
     */
    function execute()
    {
        $page = $this->_pageFactory->create();
        $page->getConfig()->getTitle()->prepend('List Questions');

        return $page;
    }
}