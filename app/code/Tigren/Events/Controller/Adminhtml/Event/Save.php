<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Adminhtml\Event;

use DateTimeZone;
use Exception;
use Zend_Filter_Input;
use Magento\Backend\App\Action;
use Magento\Backend\Helper\Js;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Filter\DateTime as DateTimeFilter;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tigren\Events\Model\EventFactory;
use Tigren\Events\Helper\Data as EventsHelper;

/**
 * Class Save
 *
 * @package Tigren\Events\Controller\Adminhtml\Event
 */
class Save extends Action
{
    /**
     * @var Filesystem
     */
    protected $_fileSystem;

    /**
     * @var UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Js
     */
    protected $jsHelper;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var EventsHelper
     */
    protected $_eventsHelper;

    /**
     * Date time filter instance
     *
     * @var DateTimeFilter
     */
    protected $_dateTimeFilter;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param Filesystem $fileSystem
     * @param UploaderFactory $fileUploaderFactory
     * @param LoggerInterface $logger
     * @param Js $jsHelper
     * @param DateTime $date
     * @param EventFactory $eventFactory
     * @param ProductFactory $productFactory
     * @param TimezoneInterface $localeDate
     * @param EventsHelper $eventsHelper
     * @param DateTimeFilter $dateTimeFilter
     */
    public function __construct(
        Action\Context $context,
        Filesystem $fileSystem,
        UploaderFactory $fileUploaderFactory,
        LoggerInterface $logger,
        Js $jsHelper,
        DateTime $date,
        EventFactory $eventFactory,
        ProductFactory $productFactory,
        TimezoneInterface $localeDate,
        EventsHelper $eventsHelper,
        DateTimeFilter $dateTimeFilter
    ) {
        parent::__construct($context);
        $this->_fileSystem = $fileSystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_logger = $logger;
        $this->jsHelper = $jsHelper;
        $this->_date = $date;
        $this->_eventFactory = $eventFactory;
        $this->_productFactory = $productFactory;
        $this->_localeDate = $localeDate;
        $this->_eventsHelper = $eventsHelper;
        $this->_dateTimeFilter = $dateTimeFilter;
    }

    /**
     * Save action
     *
     * @return ResultInterface
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /**
         * @var Redirect $resultRedirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $eventModel = $this->_objectManager->create('Tigren\Events\Model\Event');
            $id = $this->getRequest()->getParam('event_id');
            if ($id) {
                $eventModel->load($id);
                if ($id != $eventModel->getId()) {
                    throw new LocalizedException(__('The wrong event is specified.'));
                }
            }

            // Process date time
            $filterValues = ['start_time' => $this->_dateTimeFilter];
            if ($this->getRequest()->getParam('end_time')) {
                $filterValues['end_time'] = $this->_dateTimeFilter;
            }
            $inputFilter = new Zend_Filter_Input(
                $filterValues,
                [],
                $data
            );
            $data = $inputFilter->getUnescaped();

            if ($data['start_time']) {
                $data['start_time'] = $this->_eventsHelper->convertTime($data['start_time']);
            }
            if ($data['end_time']) {
                $data['end_time'] = $this->_eventsHelper->convertTime($data['end_time']);
            }
            if ($data['registration_deadline']) {
                $data['registration_deadline'] = $this->_eventsHelper->convertTime($data['registration_deadline']);
            }

            //Check if time is valid
            if ($data['start_time'] >= $data['end_time'] || $data['registration_deadline'] >= $data['end_time']) {
                if ($data['start_time'] >= $data['end_time']) {
                    $this->messageManager->addError(__('Start Time must be earlier than End Time.'));
                }
                if ($data['registration_deadline'] && $data['registration_deadline'] >= $data['end_time']) {
                    $this->messageManager->addError(__('Registration Deadline must be earlier than End Time'));
                }
                $this->_getSession()->setFormData($data);
                if ($id) {
                    return $resultRedirect->setPath('*/*/edit', ['event_id' => $id]);
                } else {
                    return $resultRedirect->setPath('*/*/new');
                }
                return $resultRedirect->setPath('*/*/', ['_current' => true]);
            }

            //Set progress_status
            $nowTime = date('Y-m-d H:i:s', time());
            $progressStatus = '';
            if ($data['start_time'] > $nowTime) {
                $progressStatus = 'upcoming';
            } else {
                if ($data['start_time'] <= $nowTime && $nowTime <= $data['end_time']) {
                    $progressStatus = 'happening';
                } else {
                    if ($data['end_time'] < $nowTime) {
                        $progressStatus = 'expired';
                    }
                }
            }
            $data['progress_status'] = $progressStatus;

            //Process categories data
            if (isset($data['categories'])) {
                $data['categories'] = array_keys($this->jsHelper->decodeGridSerializedInput($data['categories']));
            }

            $imageRequest = $this->getRequest()->getFiles('avatar');

            //Process upload images
            try {
                if (!empty($imageRequest['name'])) {
                    $path = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA)
                        ->getAbsolutePath('tigren/events/event/avatar/');
                    // remove the old file
                    $oldName = !empty($data['old_avatar']) ? $data['old_avatar'] : '';
                    if ($oldName) {
                        @unlink($path . $oldName);
                    }
                    //find the first available name
                    $newName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $imageRequest['name']);
                    if (substr($newName, 0, 1) == '.') { // all non-english symbols
                        $newName = 'event_' . $newName;
                    }
                    $i = 0;
                    while (file_exists($path . $newName)) {
                        $newName = ++$i . '_' . $newName;
                    }

                    /**
                     *
                     *
                     * @var $uploader Uploader
                     */
                    $uploader = $this->_fileUploaderFactory->create(['fileId' => 'avatar']);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->save($path, $newName);

                    $data['avatar'] = $newName;
                } else {
                    $oldName = !empty($data['old_avatar']) ? $data['old_avatar'] : '';
                    $data['avatar'] = $oldName;
                }
            } catch (Exception $e) {
                if ($e->getCode() != Uploader::TMP_NAME_EMPTY) {
                    $this->_logger->critical($e);
                }
            }

            //Process delete images
            if (!empty($data['is_delete_avatar'])) {
                $path = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA)
                    ->getAbsolutePath('tigren/events/event/avatar/');
                // remove the old file
                $oldName = !empty($data['old_avatar']) ? $data['old_avatar'] : '';
                if ($oldName) {
                    @unlink($path . $oldName);
                }
                $data['avatar'] = '';
            }

            $eventModel->setData($data);

            $this->_eventManager->dispatch(
                'events_event_prepare_save',
                ['event' => $eventModel, 'request' => $this->getRequest()]
            );

            try {
                $eventModel->save();
                $this->messageManager->addSuccess(__('You saved this Event.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['event_id' => $eventModel->getId(), '_current' => true]
                    );
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the event.'));
            }

            $this->_getSession()->setFormData($data);
            if ($id) {
                return $resultRedirect->setPath('*/*/edit', ['event_id' => $id]);
            } else {
                return $resultRedirect->setPath('*/*/new');
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Tigren_Events::save');
    }
}
