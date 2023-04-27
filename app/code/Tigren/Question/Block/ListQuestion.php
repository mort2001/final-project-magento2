<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Question\Model\ResourceModel\Post\Collection;
use Tigren\Question\Model\ResourceModel\Post\CollectionFactory;
use Magento\Customer\Model\Session;

/**
 * Class Create
 * @package Tigren\Question\Block
 */
class ListQuestion extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $collection;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Session $session
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Session $session,
        \Magento\Framework\App\Http\Context $httpContext
    )
    {
        $this->_session = $session;
        $this->collection = $collectionFactory;
        $this->httpContext = $httpContext;
        parent::__construct($context);
    }

    /**
     * @return Collection
     */
    public function getListQues()
    {
        $author='';
        if ($this->_session->isLoggedIn()){
            $author = $this->_session->getCustomerId();
        }
        $collection = $this->collection->create();
        $collection->addFieldToFilter('author_id', $author);

        return $collection;
    }
}