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
use Tigren\Events\Model\EventFactory;

/**
 * Class Sendinvitation
 *
 * @package Tigren\Events\Controller\Index
 */
class Sendinvitation extends Action
{
    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * Sendinvitation constructor.
     *
     * @param Context $context
     * @param EventFactory $eventFactory
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory
    ) {
        $this->_eventFactory = $eventFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        if ($post = $this->getRequest()->getPost()) {
            $eventId = $post['event_id'];
            $event = $this->_eventFactory->create()->load($eventId);


            try {
                $event->sendInvitationEmail($post['yourname'], $post['friendemail'], $post['invitemessage']);

                $this->messageManager->addSuccess(__('You have sent your invitation to your friend'));
                $resultRedirect = $this->resultRedirectFactory->create();
            } catch (Exception $e) {
                $this->messageManager->addError(__('Unable to send you invitation. Please try again later'));
            }
        }
        return $resultRedirect->setPath('*/*/view', ['event_id' => $event->getId()]);
    }
}
