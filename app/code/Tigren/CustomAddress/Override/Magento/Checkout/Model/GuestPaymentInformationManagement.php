<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Checkout\Model;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestBillingAddressManagementInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestPaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;

/**
 * Class PaymentInformationManagement
 *
 * @package Tigren\CustomAddress\Override\Magento\Checkout\Model
 */
class GuestPaymentInformationManagement extends \Magento\Checkout\Model\GuestPaymentInformationManagement
{
    /**
     * @var GuestBillingAddressManagementInterface
     */
    protected $billingAddressManagement;

    /**
     * @var GuestPaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var GuestCartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var PaymentInformationManagementInterface
     */
    protected $paymentInformationManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CustomAddressHelper
     */
    protected $customAddressHelper;

    /**
     * GuestPaymentInformationManagement constructor.
     *
     * @param GuestBillingAddressManagementInterface $billingAddressManagement
     * @param GuestPaymentMethodManagementInterface $paymentMethodManagement
     * @param GuestCartManagementInterface $cartManagement
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CustomAddressHelper $customAddressHelper
     */
    public function __construct(
        GuestBillingAddressManagementInterface $billingAddressManagement,
        GuestPaymentMethodManagementInterface $paymentMethodManagement,
        GuestCartManagementInterface $cartManagement,
        PaymentInformationManagementInterface $paymentInformationManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $cartRepository,
        CustomAddressHelper $customAddressHelper
    ) {
        parent::__construct(
            $billingAddressManagement,
            $paymentMethodManagement,
            $cartManagement,
            $paymentInformationManagement,
            $quoteIdMaskFactory,
            $cartRepository
        );
        $this->customAddressHelper = $customAddressHelper;
    }

    /**
     * {@inheritDoc}
     *
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function savePaymentInformation(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var Quote $quote */
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

        if ($billingAddress) {
            $billingAddress->setEmail($email);
            $quote->removeAddress($quote->getBillingAddress()->getId());

            $extensionAttributes = $billingAddress->getExtensionAttributes();
            if ($extensionAttributes) {
                if ($extensionAttributes->getIsFullInvoice()) {
                    $billingAddress->setIsFullInvoice($extensionAttributes->getIsFullInvoice());
                    $billingAddress->setTaxIdentificationNumber($extensionAttributes->getTaxIdentificationNumber());
                    $billingAddress->setHeadOffice($extensionAttributes->getHeadOffice());
                    $billingAddress->setBranchOffice($extensionAttributes->getBranchOffice());
                    $billingAddress->setCompany($extensionAttributes->getCompany());
                    $billingAddress->setPersonalFirstname($extensionAttributes->getPersonalFirstname());
                    $billingAddress->setPersonalLastname($extensionAttributes->getPersonalLastname());
                    $billingAddress->setInvoiceType($extensionAttributes->getInvoiceType());
                }

                $cityId = $extensionAttributes->getCityId();
                $subdistrictId = $extensionAttributes->getSubdistrictId();
                if ($cityId && $subdistrictId) {
                    $this->customAddressHelper->updateDataAddress($billingAddress, $cityId, $subdistrictId);
                }
            }

            $quote->setBillingAddress($billingAddress);
            $quote->setDataChanges(true);
        } else {
            $quote->getBillingAddress()->setEmail($email);
        }
        $this->limitShippingCarrier($quote);

        $this->paymentMethodManagement->set($cartId, $paymentMethod);
        return true;
    }

    /**
     * Limits shipping rates request by carrier from shipping address.
     *
     * @param Quote $quote
     *
     * @return void
     * @see \Magento\Shipping\Model\Shipping::collectRates
     */
    private function limitShippingCarrier(Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && $shippingAddress->getShippingMethod()) {
            $shippingDataArray = explode('_', $shippingAddress->getShippingMethod());
            $shippingCarrier = array_shift($shippingDataArray);
            $shippingAddress->setLimitCarrier($shippingCarrier);
        }
    }
}
