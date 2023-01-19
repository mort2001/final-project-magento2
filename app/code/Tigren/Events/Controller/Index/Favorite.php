<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Index;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Tigren\Events\Helper\Data;
use Tigren\Events\Model\EventFactory;

/**
 * Class Favorite
 *
 * @package Tigren\Events\Controller\Index
 */
class Favorite extends Action
{
    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * @var Data
     */
    protected $_eventsHelper;

    /**
     * Favorite constructor.
     *
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param Data $eventsHelper
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        Data $eventsHelper
    ) {
        $this->_eventFactory = $eventFactory;
        $this->_eventsHelper = $eventsHelper;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $eventId = $this->getRequest()->getParam('event_id');
        $action = $this->getRequest()->getParam('action');
        $currentCustomerId = $this->_eventsHelper->getCustomerId();

        if (!$currentCustomerId) {
            $this->messageManager->addError(__('You must sign in to save this event to "My Events" in your account dashboard.'));
            return $resultRedirect->setPath('*/*/view', ['event_id' => $eventId]);
        }

        try {
            if ($action == 'add') {
                $this->_eventFactory->create()->load($eventId)->addFavorite($currentCustomerId);
                $this->messageManager->addSuccess(__('This event has been added successfully to "My Events" in your account dashboard.'));
            } else {
                if ($action == 'remove') {
                    $this->_eventFactory->create()->load($eventId)->removeFavorite($currentCustomerId);
                    $this->messageManager->addSuccess(__('This event have been removed from favorite successfully.'));
                }
            }
        } catch (Exception $e) {
            $this->messageManager->addException(
                $e,
                __('There was problem when ' . ($action == 'add') ? 'add to favorite' : 'remove from favorite' . '. Please try again.')
            );
        } finally {
            return $resultRedirect->setPath('*/*/view', ['event_id' => $eventId]);
        }
    }
}
