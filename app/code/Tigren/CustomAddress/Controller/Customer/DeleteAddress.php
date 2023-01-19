<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Controller\Customer;

use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\View\Result\Page;

/**
 * Delete customer address
 */
class DeleteAddress extends Action
{
    /**
     * @var AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * DeleteAddress constructor.
     * @param Context $context
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        Context $context,
        AddressRepositoryInterface $addressRepository

    ) {
        $this->_addressRepository = $addressRepository;
        parent::__construct($context);
    }

    /**
     * Execute delete address
     *
     * @return Page
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'message' => ''
        ];

        $addressId = $this->getRequest()->getParam('id');

        try {
            $this->_addressRepository->deleteById($addressId);
            $result['success'] = true;
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $result['message'] = $e->getMessage();
        }

        return $this->getResponse()->representJson(
            $this->_objectManager->get(Data::class)->jsonEncode($result)
        );
    }
}
