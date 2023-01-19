<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Controller\Adminhtml\Block;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Tigren\BannerManager\Model\Block;

/**
 * Class Delete
 *
 * @package Tigren\BannerManager\Controller\Adminhtml\Block
 */
class Delete extends Action
{
    /**
     * @param Action\Context $context
     */
    public function __construct(Action\Context $context)
    {
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('block_id');
        if ($id) {
            try {
                /**
                 *
                 *
                 * @var Block $model
                 */
                $model = $this->_objectManager->create('Tigren\BannerManager\Model\Block');
                $model->load($id);
                $model->delete();
                $this->_redirect('bannersmanager/*/');
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete this block right now. Please review the log and try again.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('bannersmanager/*/edit', ['block_id' => $this->getRequest()->getParam('block_id')]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a rule to delete.'));
        $this->_redirect('bannersmanager/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Tigren_BannerManager::block');
    }
}
