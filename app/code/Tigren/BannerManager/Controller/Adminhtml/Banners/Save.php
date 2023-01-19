<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Controller\Adminhtml\Banners;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Helper\Js;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Filter\DateTime as DateTimeFilter;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface as Logger;
use RuntimeException;
use Tigren\BannerManager\Helper\Data;
use Tigren\BannerManager\Model\Banner;
use Tigren\BannerManager\Model\BannerFactory;
use Zend_Filter_Input;

/**
 * Class Save
 *
 * @package Tigren\BannerManager\Controller\Adminhtml\Banners
 */
class Save extends Action
{
    /**
     *
     */
    const IMAGE_FIELDS = ['banner_image', 'mobile_image'];

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Js
     */
    protected $jsHelper;

    /**
     * Date time filter instance
     *
     * @var DateTimeFilter
     */
    protected $_dateTimeFilter;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * File system
     *
     * @var Filesystem
     */
    protected $_fileSystem;

    /**
     * File Uploader factory
     *
     * @var UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var BannerFactory
     */
    protected $_bannerFactory;

    /**
     * @var Data
     */
    protected $_bannerManagerHelper;

    /**
     * @param Action\Context $context
     * @param TypeListInterface $cacheTypeList
     * @param Js $jsHelper
     * @param DateTimeFilter $dateTimeFilter
     * @param DateTime $date
     * @param Filesystem $fileSystem
     * @param UploaderFactory $fileUploaderFactory
     * @param Logger $logger
     * @param BannerFactory $bannerFactory
     * @param Data $bannerManagerHelper
     */
    public function __construct(
        Action\Context $context,
        TypeListInterface $cacheTypeList,
        Js $jsHelper,
        DateTimeFilter $dateTimeFilter,
        DateTime $date,
        Filesystem $fileSystem,
        UploaderFactory $fileUploaderFactory,
        Logger $logger,
        BannerFactory $bannerFactory,
        Data $bannerManagerHelper
    ) {
        parent::__construct($context);
        $this->_dateTimeFilter = $dateTimeFilter;
        $this->_date = $date;
        $this->_fileSystem = $fileSystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_logger = $logger;
        $this->jsHelper = $jsHelper;
        $this->cacheTypeList = $cacheTypeList;
        $this->_bannerFactory = $bannerFactory;
        $this->_bannerManagerHelper = $bannerManagerHelper;
    }

    /**
     * Save action
     *
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /**
         *
         *
         * @var Redirect $resultRedirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
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
                $data['start_time'] = $this->_bannerManagerHelper->convertDateTime($data['start_time']);
            }
            if ($data['end_time']) {
                $data['end_time'] = $this->_bannerManagerHelper->convertDateTime($data['end_time']);
            }

            /**
             *
             *
             * @var Banner $model
             */
            $model = $this->_bannerFactory->create();

            $id = $this->getRequest()->getParam('banner_id');
            if ($id) {
                $model->load($id);
                if ($id != $model->getId()) {
                    throw new LocalizedException(__('The wrong banner is specified.'));
                }
                $data['update_time'] = $this->_date->gmtDate();
            } else {
                $data['created_time'] = $this->_date->gmtDate();
                $data['update_time'] = $this->_date->gmtDate();
            }

            // Upload images
            $this->uploadImage($data);

            if (isset($data['blocks'])) {
                $data['blocks'] = array_keys($this->jsHelper->decodeGridSerializedInput($data['blocks']));
            }

            $model->setData($data);

            $this->_eventManager->dispatch(
                'bannermanager_banner_prepare_save',
                ['banner' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                $this->cacheTypeList->invalidate('full_page');
                $this->messageManager->addSuccess(__('You saved this Banner.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['image_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the banner.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['image_id' => $this->getRequest()->getParam('banner_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $data
     */
    public function uploadImage(&$data)
    {
        foreach (self::IMAGE_FIELDS as $field) {
            $imageRequest = $this->getRequest()->getFiles($field);

            $path = $this->_fileSystem->getDirectoryRead(
                DirectoryList::MEDIA
            )->getAbsolutePath(
                'tigren/banners/'
            );

            $oldField = 'old_' . $field;
            try {
                if (!empty($imageRequest['name'])) {
                    $oldName = !empty($data[$oldField]) ? $data[$oldField] : '';
                    if ($oldName) {
                        @unlink($path . $oldName);
                    }

                    //find the first available name
                    $newName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $imageRequest['name']);
                    if (substr($newName, 0, 1) == '.') { // all non-english symbols
                        $newName = 'banner_' . $newName;
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
                    $uploader = $this->_fileUploaderFactory->create(['fileId' => $field]);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->save($path, $newName);

                    $data[$field] = $newName;
                } else {
                    $oldName = !empty($data[$oldField]) ? $data[$oldField] : '';
                    $data[$field] = $oldName;
                }
            } catch (Exception $e) {
                if ($e->getCode() != Uploader::TMP_NAME_EMPTY) {
                    $this->_logger->critical($e);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Tigren_BannerManager::banner');
    }
}
