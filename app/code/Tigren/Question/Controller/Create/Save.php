<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Controller\Create;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Tigren\Question\Model\PostFactory;

/**
 * Class Save
 * @package Tigren\Question\Controller\Create
 */
class Save extends Action
{
    /**
     * @var PostFactory
     */
    protected $_postFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerRepository
     */
    protected $customerFactory;

    /**
     * @var Customer
     */
    protected $cus;

    /**
     * @param Context $context
     * @param PostFactory $postFactory
     * @param Session $session
     * @param CustomerFactory $customerFactory
     * @param Customer $cus
     */
    public function __construct(
        Context         $context,
        PostFactory     $postFactory,
        Session         $session,
        CustomerFactory $customerFactory,
        Customer        $cus
    )
    {
        $this->cus = $cus;
        $this->customerFactory = $customerFactory;
        $this->session = $session;
        $this->_postFactory = $postFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface
     * @throws Exception
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $model_data = $this->_postFactory->create();
        $authorId = $this->session->getCustomer()->getId();
        $data['author_id'] = $authorId;
        $model_data->addData($data);
        $model_data->save();

        $customerId = $this->session->getCustomerId();
        $customer = $this->cus->load($customerId);
        $customerData = $customer->getDataModel();

        $datayn = "Yes";
        $customerData->setCustomAttribute('is_question_created', $datayn);
        $customer->updateData($customerData);
        $customerResource = $this->customerFactory->create();
        $customerResource->saveAttribute($customer, 'is_question_created');
        $this->messageManager->addSuccessMessage("Saved Data");

        return $this->_redirect('question/create/listquestion');
    }
}