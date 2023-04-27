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
 * Class ListQuestion
 * @package Tigren\Question\Controller\Create
 */
class ListQuestion extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Session
     */
    protected $_sesstion;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $session
     */
    public function __construct(
        Context     $context,
        PageFactory $resultPageFactory,
        Session     $session
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_sesstion = $session;
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        if ($this->_sesstion->isLoggedIn()) {
            return $this->resultPageFactory->create();
        } else {
            $this->messageManager->addErrorMessage('Please Login first before doing anything ...');

            return $this->_redirect('customer/account/login');
        }
    }
}