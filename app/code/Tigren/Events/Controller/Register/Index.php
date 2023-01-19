<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Register;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Tigren\Events\Model\EventFactory;

/**
 * Class Index
 *
 * @package Tigren\Events\Controller\Register
 */
class Index extends Action
{
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param EventFactory $eventFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        EventFactory $eventFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_eventFactory = $eventFactory;
        parent::__construct($context);
    }

    /**
     * @return bool|ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $eventId = (int)$this->getRequest()->getParam('event_id', false);
        if (!$eventId) {
            return false;
        }
        $event = $this->_eventFactory->create()->load($eventId);

        if ($event->getId()) {
            $this->_coreRegistry->register('events_event', $event);
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('Event Registration'));

            return $resultPage;
        } else {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/index/index');
        }
    }
}
