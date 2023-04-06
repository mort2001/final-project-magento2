<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Checkout\Model;

use Exception;
use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\PaymentDetailsFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\QuoteAddressValidator;
use Psr\Log\LoggerInterface;
use function Sodium\add;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;

/**
 * Class ShippingInformationManagemen
 * @package Tigren\CustomAddress\Override\Magento\Checkout\Model
 */
class ShippingInformationManagement extends \Magento\Checkout\Model\ShippingInformationManagement
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var
     */
    protected $cartExtensionFactory;

    /**
     * @var
     */
    protected $shippingFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * @var QuoteAddressValidator
     */
    protected $addressValidator;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var CustomAddressHelper
     */
    protected $customAddressHelper;

    /**
     * ShippingInformationManagement constructor.
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentDetailsFactory $paymentDetailsFactory
     * @param CartTotalRepositoryInterface $cartTotalsRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteAddressValidator $addressValidator
     * @param LoggerInterface $logger
     * @param AddressRepositoryInterface $addressRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param TotalsCollector $totalsCollector
     * @param CustomAddressHelper $customAddressHelper
     */
    public function __construct(
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentDetailsFactory $paymentDetailsFactory,
        CartTotalRepositoryInterface $cartTotalsRepository,
        CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        LoggerInterface $logger,
        AddressRepositoryInterface $addressRepository,
        ScopeConfigInterface $scopeConfig,
        TotalsCollector $totalsCollector,
        CustomAddressHelper $customAddressHelper
    ) {
        parent::__construct(
            $paymentMethodManagement,
            $paymentDetailsFactory,
            $cartTotalsRepository,
            $quoteRepository,
            $addressValidator,
            $logger,
            $addressRepository,
            $scopeConfig,
            $totalsCollector
        );

        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->quoteRepository = $quoteRepository;
        $this->addressValidator = $addressValidator;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->totalsCollector = $totalsCollector;
        $this->customAddressHelper = $customAddressHelper;
    }

    /**
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return PaymentDetailsInterface
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function saveAddressInformation(
        $cartId,
        ShippingInformationInterface $addressInformation
    ): PaymentDetailsInterface {
        $address = $addressInformation->getShippingAddress();
        $billingAddress = $addressInformation->getBillingAddress();
        $carrierCode = $addressInformation->getShippingCarrierCode();
        $methodCode = $addressInformation->getShippingMethodCode();

        if (!$address->getCustomerAddressId()) {
            $address->setCustomerAddressId(null);
        }

        if (!$address->getCountryId()) {
            throw new StateException(__('Shipping address is not set'));
        }

        $extAttributes = $address->getExtensionAttributes();
        if ($extAttributes) {
            $address->setIsFullInvoice('1');
            $address->setTaxIdentificationNumber($extAttributes->getTaxIdentificationNumber());
            $address->setPhone($extAttributes->getTelephone());
            $address->setInvoiceType($extAttributes->getInvoiceType());
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/a.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('message ' . print_r($extAttributes, true));
            if ($extAttributes->getInvoiceType() === 'corporate') {
                $address->setCompanyName($extAttributes->getcompany());
                $address->setPersonalFirstname('');
                $address->setPersonalLastname('');
                if ($extAttributes->getCompanyBranch() === 'head') {
                    $address->setHeadOffice('1');
                    $address->setBranchOffice('');
                } elseif ($extAttributes->getCompanyBranch() === 'branch') {
                    $address->setBranchOffice('1');
                    $address->setHeadOffice('');
                    $address->setBranch($extAttributes->getBranchName());
                }
            } else {
                $address->setPersonalFirstname($extAttributes->getPersonalFirstname());
                $address->setPersonalLastname($extAttributes->getPersonalLastname());
                $address->setCompanyName('');
                $address->setHeadOffice('');
                $address->setBranchOffice('');
                $address->setBranch('');
            }
        }

        $extAttributesBilling = $billingAddress->getExtensionAttributes();

        $quote = $this->quoteRepository->getActive($cartId);
        $quote = $this->prepareShippingAssignment($quote, $address, $carrierCode . '_' . $methodCode);
        $this->validateQuote($quote);
        $quote->setIsMultiShipping(false);

        if ($extAttributesBilling->getSubdistrict()) {
            $billingAddress->setSubdistrict($extAttributesBilling->getSubdistrict());
        }

        $quote->setBillingAddress($billingAddress);

        try {
            $this->quoteRepository->save($quote);
        } catch (Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Unable to save shipping information. Please check input data.'));
        }

        $shippingAddress = $quote->getShippingAddress();
        $updateRegionId = false;
        if (!$address->getData('region_id') && is_numeric($address->getData('region'))) {
            $shippingAddress->setRegionId(null);
            $updateRegionId = true;
        }
        if (!$shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod())) {
            throw new NoSuchEntityException(
                __('Carrier with such method not found: %1, %2', $carrierCode, $methodCode)
            );
        }

        // add more data address.
        $updateCustomAttribute = false;
        if ($extAttributes) {
            $cityId = $extAttributes->getCityId();
            $subdistrictId = $extAttributes->getSubdistrictId();
            if ($extAttributes->getSubdistrict()) {
                $shippingAddress->setSubdistrict($extAttributes->getSubdistrict());
                $updateCustomAttribute = true;
            }
            if ($cityId && $subdistrictId) {
                $this->customAddressHelper->updateDataAddress($shippingAddress, $cityId, $subdistrictId);
                $updateCustomAttribute = true;
            }
        }
        if ($updateCustomAttribute || $updateRegionId) {
            $shippingAddress->save();
        }

        if ($this->customAddressHelper->getMoveBilling()) {
            $billingAddress = $quote->getBillingAddress();
            if ($extAttributesBilling) {
                $cityId = $extAttributesBilling->getCityId();
                $subdistrictId = $extAttributesBilling->getSubdistrictId();
                if ($extAttributesBilling->getSubdistrict()) {
                    $billingAddress->setSubdistrict($extAttributesBilling->getSubdistrict());
                }
                if ($cityId && $subdistrictId) {
                    $this->customAddressHelper->updateDataAddress($billingAddress, $cityId, $subdistrictId);
                }
                $billingAddress->save();
            }
        }

        /** @var PaymentDetailsInterface $paymentDetails */
        $paymentDetails = $this->paymentDetailsFactory->create();
        $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
        $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));

        return $paymentDetails;
    }

    /**
     * @param CartInterface $quote
     * @param AddressInterface $address
     * @param string $method
     * @return CartInterface
     */
    private function prepareShippingAssignment(CartInterface $quote, AddressInterface $address, $method)
    {
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }

        $shippingAssignments = $cartExtension->getShippingAssignments();
        if (empty($shippingAssignments)) {
            $shippingAssignment = $this->shippingAssignmentFactory->create();
        } else {
            $shippingAssignment = $shippingAssignments[0];
        }

        $shipping = $shippingAssignment->getShipping();
        if ($shipping === null) {
            $shipping = $this->shippingFactory->create();
        }

        $shipping->setAddress($address);
        $shipping->setMethod($method);
        $shippingAssignment->setShipping($shipping);
        $cartExtension->setShippingAssignments([$shippingAssignment]);

        return $quote->setExtensionAttributes($cartExtension);
    }
}
