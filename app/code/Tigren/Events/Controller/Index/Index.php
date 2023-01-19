<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Index
 *
 * @package Tigren\Events\Controller\Index
 */
class Index extends Action
{
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $viewMode = $this->getRequest()->getParam('mode');
        if ($viewMode) {
            $this->_coreRegistry->register('current_view_mode', $viewMode);
        }

        $catId = (int)$this->getRequest()->getPost('category', false);
        if ($catId) {
            $this->_coreRegistry->register('filter_cat_id', $catId);
        }
        $eventSearch = $this->getRequest()->getPost('event', false);
        if ($eventSearch) {
            $this->_coreRegistry->register('event_search', trim($eventSearch));
        }
        $locationSearch = $this->getRequest()->getPost('location', false);
        if ($locationSearch) {
            $this->_coreRegistry->register('location_search', trim($locationSearch));
        }

        $resultPage = $this->resultPageFactory->create();
        $pageTitle = $this->_scopeConfig->getValue(
            'events/calendar_setting/page_title',
            ScopeInterface::SCOPE_STORE
        );
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        return $resultPage;
    }
}
