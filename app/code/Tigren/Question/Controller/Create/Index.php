<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Controller\Create;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Tigren\Question\Controller\Create
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var Session
     */
    protected $_sesstion;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Session $session
     */
    public function __construct(
        Context     $context,
        PageFactory $pageFactory,
        Session     $session
    )
    {
        $this->_sesstion = $session;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        if ($this->_sesstion->isLoggedIn()) {
            return $this->_pageFactory->create();
        } else {
            $this->messageManager->addErrorMessage('Please Login first before doing anything ...');

            return $this->_redirect('customer/account/login');
        }
    }
}