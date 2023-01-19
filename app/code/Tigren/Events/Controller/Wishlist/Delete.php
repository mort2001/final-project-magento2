<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Wishlist;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Tigren\Events\Helper\Data;
use Tigren\Events\Model\EventFactory;

/**
 * Class Delete
 *
 * @package Tigren\Events\Controller\Wishlist
 */
class Delete extends Action
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
     * Delete constructor.
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
        $currentCustomerId = $this->_eventsHelper->getCustomerId();

        try {
            $this->_eventFactory->create()->load($eventId)->removeFavorite($currentCustomerId);
            $this->messageManager->addSuccess(__('You have deleted successfully.'));
        } catch (Exception $e) {
            $this->messageManager->addException($e, __('There was problem when delete item.'));
        } finally {
            return $resultRedirect->setPath('*/*/index');
        }
    }
}
