<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Controller\Create;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Tigren\Question\Model\PostFactory;

/**
 * Class Update
 * @package Tigren\Question\Controller\Create
 */
class Update extends Action
{
    /**
     * @var PostFactory
     */
    protected $postFactory;

    /**
     * @var
     */
    protected $session;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @param PostFactory $postFactory
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        PostFactory $postFactory,
        Context     $context,
        PageFactory $pageFactory,

    )
    {
        $this->postFactory = $postFactory;
        $this->pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    /**
     * @return Page|Redirect|ResultInterface
     * @throws Exception
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $id = $data['id'];
        $newData = [
            'title' => $data['title'],
            'content' => $data['content']
        ];

        $post = $this->postFactory->create()->load($id);
        if (isset($newData)) {
            $post->addData($newData);
            $post->save();
            $this->messageManager->addSuccessMessage(__('You update the question success.'));
            $resutl = $this->resultRedirectFactory->create()->setPath('question/create/listquestion');
        } else {
            $this->messageManager->addErrorMessage('Error');
            return $this->pageFactory->create();
        }

        return $resutl;
    }
}