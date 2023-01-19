<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Controller\Adminhtml\Block;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Helper\Js;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Filter\DateTime as DateTimeFilter;
use RuntimeException;
use Tigren\BannerManager\Helper\Data;
use Tigren\BannerManager\Model\Block;
use Tigren\BannerManager\Model\BlockFactory;
use Zend_Filter_Input;

/**
 * Class Save
 *
 * @package Tigren\BannerManager\Controller\Adminhtml\Block
 */
class Save extends Action
{
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
     * @var BlockFactory
     */
    protected $_blockFactory;

    /**
     * @var Data
     */
    protected $_bannerManagerHelper;

    /**
     * @param Action\Context $context
     * @param TypeListInterface $cacheTypeList
     * @param DateTimeFilter $dateTimeFilter
     * @param DateTime $date
     * @param Js $jsHelper
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        Action\Context $context,
        TypeListInterface $cacheTypeList,
        DateTimeFilter $dateTimeFilter,
        DateTime $date,
        Js $jsHelper,
        BlockFactory $blockFactory,
        Data $bannerManagerHelper
    ) {
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
        $this->jsHelper = $jsHelper;
        $this->_blockFactory = $blockFactory;
        $this->_dateTimeFilter = $dateTimeFilter;
        $this->_bannerManagerHelper = $bannerManagerHelper;
    }

    /**
     * Save action
     *
     * @return ResultInterface
     * @throws Exception
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
            $filterValues = ['from_date' => $this->_dateTimeFilter];
            if ($this->getRequest()->getParam('to_date')) {
                $filterValues['to_date'] = $this->_dateTimeFilter;
            }
            $inputFilter = new Zend_Filter_Input(
                $filterValues,
                [],
                $data
            );
            $data = $inputFilter->getUnescaped();

            if ($data['from_date']) {
                $data['from_date'] = $this->_bannerManagerHelper->convertDateTime($data['from_date']);
            }
            if ($data['to_date']) {
                $data['to_date'] = $this->_bannerManagerHelper->convertDateTime($data['to_date']);
            }

            /**
             *
             *
             * @var Block $model
             */
            $model = $this->_blockFactory->create();

            $id = $this->getRequest()->getParam('block_id');
            if ($id) {
                $model->load($id);
            }

            if (!empty($data['category'])) {
                $data['category'] = implode(',', $data['category']);
            }

            if (isset($data['banners'])) {
                $data['banners'] = $this->jsHelper->decodeGridSerializedInput($data['banners']);
            }

            $model->setData($data);

            $this->_eventManager->dispatch(
                'bannermanager_block_prepare_save',
                ['block' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                $this->cacheTypeList->invalidate('full_page');
                $this->messageManager->addSuccess(__('You saved this Block.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['block_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the block.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['block_id' => $this->getRequest()->getParam('block_id')]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Tigren_BannerManager::block');
    }
}
