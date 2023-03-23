<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Controller\Adminhtml\Rule;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Tigren\CustomerGroupCatalog\Model\RuleFactory;

/**
 * Class Save
 * @package Tigren\CustomerGroupCatalog\Controller\Adminhtml\Rule
 */
class Save extends Action
{
    /**
     * @var RuleFactory
     */
    public $ruleFactory;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        Action\Context $context,
        RuleFactory    $ruleFactory
    )
    {
        $this->ruleFactory = $ruleFactory;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $id = $data['rule_id'] ?? null;
        if ($id) {
            $rule = $this->ruleFactory->create()->load($id);
        } else {
            $rule = $this->ruleFactory->create();
        }
        $arr = [
            'name' => $data['name'],
            'products' => $data['products'],
            'discount_amount' => $data['discount_amount'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'store_ids' => implode(',', $data['store_ids']),
            'priority' => $data['priority'],
            'is_active' => $data['is_active'],
            'customer_group_ids' => implode(',', $data['customer_group_ids'])
        ];
        try {
            $rule->addData($arr);
            $rule->save();
            $this->messageManager->addSuccessMessage(__('You had saved the rule'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('tigren_customergroup/rule/index');
    }
}