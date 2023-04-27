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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

/**
 * Class Edit
 * @package Tigren\Question\Controller\Create
 */
class Edit extends Action
{
    /**
     * @var Session
     */
    protected $_session;

    /**
     * @param Context $context
     * @param Session $session
     */
    public function __construct(Context $context, Session $session)
    {
        $this->_session = $session;
        parent::__construct($context);
    }

    /**
     * @return Page|ResponseInterface|
     */
    public function execute()
    {
        if ($this->_session->isLoggedIn()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $result->getConfig()->getTitle();

            return $result;
        } else {
            return $this->_redirect('customer/account/login');
        }
    }
}