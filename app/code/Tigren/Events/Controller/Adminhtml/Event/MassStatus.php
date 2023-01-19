<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Adminhtml\Event;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Tigren\Events\Model\ParticipantFactory;
use Tigren\Events\Model\ResourceModel\Participant\CollectionFactory;

/**
 * Class MassDisable
 *
 * @package Tigren\Events\Controller\Adminhtml\Event
 */
class MassStatus extends Action
{
    /**
     * @var ParticipantFactory
     */
    protected $participantFactory;

    /**
     * @var CollectionFactory
     */
    protected $participantCollectionFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * MassStatus constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param ParticipantFactory $participantFactory
     * @param CollectionFactory $participantCollectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        ParticipantFactory $participantFactory,
        CollectionFactory $participantCollectionFactory
    ) {
        $this->filter = $filter;
        $this->participantFactory = $participantFactory;
        $this->participantCollectionFactory = $participantCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     * @throws LocalizedException|Exception
     */
    public function execute()
    {
        $status = $this->getRequest()->getParam('status');
        $id = $this->getRequest()->getParam('participant');

        $collection = $this->participantFactory->create()->getCollection()
            ->addFieldToFilter('participant_id', $id);

        foreach ($collection as $review) {
            $review->setStatus($status);
            $review->setIdFieldName('participant_id');
            $review->save();
        }

        $collectionSize = $collection->getSize();
        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been updated.', $collectionSize)
        );

        /**
         *
         *
         * @var Redirect $resultRedirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/edit', ['event_id' => $this->getRequest()->getParam('event_id')]);
    }
}
