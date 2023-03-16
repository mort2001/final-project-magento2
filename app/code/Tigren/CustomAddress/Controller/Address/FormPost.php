<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Controller\Address;

use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\View\Result\PageFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FormPost extends \Magento\Customer\Controller\Address\FormPost
{
    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * FormPost constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param FormFactory $formFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param DataObjectProcessor $dataProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $resultPageFactory
     * @param RegionFactory $regionFactory
     * @param HelperData $helperData
     * @param Data $jsonHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        FormFactory $formFactory,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory,
        DataObjectProcessor $dataProcessor,
        DataObjectHelper $dataObjectHelper,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        RegionFactory $regionFactory,
        HelperData $helperData,
        Data $jsonHelper
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $formKeyValidator,
            $formFactory,
            $addressRepository,
            $addressDataFactory,
            $regionDataFactory,
            $dataProcessor,
            $dataObjectHelper,
            $resultForwardFactory,
            $resultPageFactory,
            $regionFactory,
            $helperData
        );
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Process address form save
     *
     * @return Redirect
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $result = ['success' => false, 'message' => __('Something went wrong while saving the address data.')];
            return $this->getResponse()->representJson($this->jsonHelper->jsonEncode($result));
        }

        if (!$this->getRequest()->isPost()) {
            $result = ['success' => false, 'message' => __('Something went wrong while saving the address data.')];
            return $this->getResponse()->representJson($this->jsonHelper->jsonEncode($result));
        }

        try {
            $params = $this->getRequest()->getParams();

            if (isset($params['default_shipping'])) {
                $params['default_shipping'] = $params['default_shipping'] === 'true' ? 1 : 0;
            }

            if (isset($params['default_billing'])) {
                $params['default_billing'] = $params['default_billing'] === 'true' ? 1 : 0;
            }

            $this->getRequest()->setParams($params);

            $address = $this->_extractAddress();
            $this->_addressRepository->save($address);
            $result = ['success' => true, 'message' => __('You saved the address.')];
        } catch (InputException $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->getResponse()->representJson($this->jsonHelper->jsonEncode($result));
    }
}
