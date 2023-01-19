<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Tigren\Events\Helper\Data as EventsHelper;

/**
 * Class Edit
 *
 * @package Tigren\Events\Controller\Adminhtml\Event
 */
class Edit extends Action
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
     * @var EventsHelper
     */
    protected $_eventsHelper;

    /**
     * Edit constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param EventsHelper $eventsHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        EventsHelper $eventsHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_eventsHelper = $eventsHelper;
        parent::__construct($context);
    }

    /**
     * Edit Action
     *
     * @return                                  Page|Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws \Exception
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('event_id');
        $event = $this->_objectManager->create('Tigren\Events\Model\Event');

        if ($id) {
            $event->load($id);
            if (!$event->getId()) {
                $this->messageManager->addError(__('This event no longer exists.'));
                /**
                 * \Magento\Backend\Model\View\Result\Redirect $resultRedirect
                 */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $event->setData($data);
        }

        $this->_coreRegistry->register('events_event', $event);

        /**
         *
         *
         * @var Page $resultPage
         */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Events') : __('New Events'),
            $id ? __('Edit Events') : __('New Events')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Events'));
        $resultPage->getConfig()->getTitle()
            ->prepend($event->getId() ? __('Edit Event ') . $event->getTitle() : __('New Event'));

        return $resultPage;
    }

    /**
     * Init actions
     *
     * @return Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /**
         *
         *
         * @var Page $resultPage
         */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Tigren_Events::manage_events')
            ->addBreadcrumb(__('Events'), __('Events'))
            ->addBreadcrumb(__('Manage Events'), __('Manage Events'));
        return $resultPage;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Tigren_Events::save');
    }
}
