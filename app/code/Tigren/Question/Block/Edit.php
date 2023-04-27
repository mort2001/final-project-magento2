<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Block;

use Magento\Framework\View\Element\Template;
use Tigren\Question\Model\Post;
use Tigren\Question\Model\PostFactory;

/**
 * Class Edit
 * @package Tigren\Question\Block
 */
class Edit extends Template
{
    /**
     * @var PostFactory
     */
    protected $postFactory;

    /**
     * @param Template\Context $context
     * @param PostFactory $postFactory
     */
    public function __construct(
        Template\Context $context,
        PostFactory      $postFactory
    )
    {
        $this->postFactory = $postFactory;
        parent::__construct($context);
    }

    /**
     * @return Post|void
     */
    public function getQuestion()
    {
        $id = $this->getRequest()->getParam('id');
        $post = $this->postFactory->create()->load($id);
        if ($post->getEntityId()) {
            return $post;
        }
    }
}