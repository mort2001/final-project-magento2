<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Controller\Adminhtml\Block;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Tigren\BannerManager\Model\ResourceModel\Block\CollectionFactory;

/**
 * Class MassStatus
 *
 * @package Tigren\BannerManager\Controller\Adminhtml\Block
 */
class MassStatus extends Index
{
    /**
     *
     */
    const STATUS_ENABLED = 1;

    /**
     *
     */
    const STATUS_DISABLED = 0;

    /**
     * MassActions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $resultPageFactory);
    }

    /**
     * Update product(s) status action
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $blockIds = $collection->getAllIds();
        $status = (int)$this->getRequest()->getParam('status');

        try {
            foreach ($collection->getItems() as $block) {
                $block->setIsActive($status);
                $block->save();
            }
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been updated.', count($blockIds)));
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e, __('Something went wrong while updating the block(s) status.'));
        }

        /**
         *
         *
         * @var Redirect $resultRedirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('bannersmanager/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Tigren_BannerManager::block');
    }
}
