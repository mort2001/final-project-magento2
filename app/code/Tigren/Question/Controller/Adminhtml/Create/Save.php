<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Controller\Adminhtml\Create;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Tigren\Question\Model\PostFactory;

/**
 * Class Save
 * @package Tigren\Customer\Controller\Adminhtml\Post
 */
class Save extends Action
{
    /**
     * @var PostFactory
     */
    public $postFactory;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param PostFactory $postFactory
     */
    public function __construct(
        Action\Context $context,
        PostFactory    $postFactory
    )
    {
        parent::__construct($context);
        $this->postFactory = $postFactory;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $id = $data['entity_id'] ?? null;
        if ($id) {
            $post = $this->postFactory->create()->load($id);
        } else {
            $post = $this->postFactory->create();
        }
        $arr = [
            'title' => $data['title'],
            'content' => $data['content']
        ];
        try {
            $post->addData($arr);
            $post->save();
            $this->messageManager->addSuccessMessage(__('You had saved the question'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('question/create/index');
    }
}