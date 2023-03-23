<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Controller\Adminhtml;

use Tigren\CustomerGroupCatalog\Model\RuleFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

/**
 * Class Rule
 * @package Tigren\CustomerGroupCatalog\Controller\Adminhtml
 */
abstract class Rule extends Action
{
    public const ADMIN_RESOURCE = 'Tigren_CustomerGroupCatalog::rule';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * Rule constructor
     * @param Context $context
     * @param Registry $coreRegistry
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        Context     $context,
        Registry    $coreRegistry,
        RuleFactory $ruleFactory
    )
    {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * @return Page
     */
    protected function _initAction()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Customer Group Catalog Rules'), __('Customer Group Catalog Rules'));

        return $resultPage;
    }
}
