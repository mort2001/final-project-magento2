<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Register;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\ScopeInterface;
use Tigren\Events\Model\EventFactory;
use Tigren\Events\Model\ParticipantFactory;

/**
 * Class Submitregistration
 *
 * @package Tigren\Events\Controller\Register
 */
class Submitregistration extends Action
{
    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * @var ParticipantFactory
     */
    protected $_participantFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Submitregistration constructor.
     *
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param ParticipantFactory $participantFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        ParticipantFactory $participantFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_eventFactory = $eventFactory;
        $this->_participantFactory = $participantFactory;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $post = $this->getRequest()->getPost();
        if ($post && !empty($post['event_id'])) {
            $eventId = $post['event_id'];
            $event = $this->_eventFactory->create()->load($eventId);

            //Check if allow to register
            $participantIds = $event->getParticipantIds();
            $remainSlotCount = ((int)$event->getRemainSlotCount());
            if ($remainSlotCount <= 0) {
                $this->messageManager->addError(__('This event was full of slot. So you can not register.'));
                return $resultRedirect->setPath('*/index/view', ['event_id' => $eventId]);
            }

            $participant = $this->_participantFactory->create();
            try {
                //Save info of participant
                $data = [
                    'event_id' => $post['event_id'],
                    'fullname' => $post['fullname'],
                    'email' => $post['email'],
                    'phone' => $post['phone'],
                    'address' => $post['address']
                ];
                $participant->setData($data)
                    ->setStatus(0)
                    ->save();

                //Send email
                if ($this->_scopeConfig->getValue(
                        'events/general_setting/is_send_registered_email',
                        ScopeInterface::SCOPE_STORE
                    ) == 1
                ) {
                    $event->sendRegisteredEmail($data);
                    $this->messageManager->addSuccess(__('Thank you for your registration. We sent an email to you about your registration.'));
                } else {
                    $this->messageManager->addSuccess(__('Thank you for your registration. We will contact you to confirm.'));
                }
                return $resultRedirect->setPath('*/index/view', ['event_id' => $eventId]);
            } catch (Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('There was problem when submitting your request. Please try again.')
                );
                return $resultRedirect->setPath('*/*/index', ['event_id' => $eventId]);
            }
        }
    }
}
