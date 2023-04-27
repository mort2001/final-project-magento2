<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Tigren\Question\Model\ResourceModel\Post\Collection;
use Tigren\Question\Model\ResourceModel\Post\CollectionFactory;

/**
 * Class Data
 * @package Tigren\Question\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var CollectionFactory
     */
    protected $colelctionFactory;

    /**
     * @param Context $context
     * @param Session $session
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context           $context,
        Session           $session,
        CollectionFactory $collectionFactory
    )
    {
        $this->colelctionFactory = $collectionFactory;
        $this->_session = $session;
        parent::__construct($context);
    }

    /**
     * @return Collection
     */
    public function getListQues()
    {
        $author = '';
        if ($this->_session->isLoggedIn()) {
            $author = $this->_session->getCustomerId();
        }
        $collection = $this->colelctionFactory->create();
        $collection->addFieldToFilter('author_id', $author);

        return $collection;
    }
}