<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Checkout\Model;

use Magento\Checkout\Model\PaymentDetailsFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;
use Tigren\CustomAddress\Model\CityFactory;
use Tigren\CustomAddress\Model\SubdistrictFactory;

/**
 * Class PaymentInformationManagement
 * @package Tigren\CustomAddress\Override\Magento\Checkout\Model
 */
class PaymentInformationManagement extends \Magento\Checkout\Model\PaymentInformationManagement
{
    /**
     * @var CityFactory
     */
    protected $cityFactory;

    /**
     * @var SubdistrictFactory
     */
    protected $subdistrictFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CustomAddressHelper
     */
    protected $customAddressHelper;

    /**
     * @param BillingAddressManagementInterface $billingAddressManagement
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param CartManagementInterface $cartManagement
     * @param PaymentDetailsFactory $paymentDetailsFactory ,
     * @param CartTotalRepositoryInterface $cartTotalsRepository
     * @param CityFactory $cityFactory
     * @param SubdistrictFactory $subdistrictFactory
     * @param CustomAddressHelper $customAddressHelper
     * @codeCoverageIgnore
     */
    public function __construct(
        BillingAddressManagementInterface $billingAddressManagement,
        PaymentMethodManagementInterface $paymentMethodManagement,
        CartManagementInterface $cartManagement,
        PaymentDetailsFactory $paymentDetailsFactory,
        CartTotalRepositoryInterface $cartTotalsRepository,
        CityFactory $cityFactory,
        SubdistrictFactory $subdistrictFactory,
        CustomAddressHelper $customAddressHelper
    ) {
        parent::__construct(
            $billingAddressManagement,
            $paymentMethodManagement,
            $cartManagement,
            $paymentDetailsFactory,
            $cartTotalsRepository
        );
        $this->cityFactory = $cityFactory;
        $this->subdistrictFactory = $subdistrictFactory;
        $this->customAddressHelper = $customAddressHelper;
    }

    /**
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return bool
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function savePaymentInformation(
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if ($billingAddress) {
            /** @var CartRepositoryInterface $quoteRepository */
            $quoteRepository = $this->getCartRepository();
            /** @var Quote $quote */
            $quote = $quoteRepository->getActive($cartId);
            $customerId = $quote->getBillingAddress()
                ->getCustomerId();
            if (!$billingAddress->getCustomerId() && $customerId) {
                //It's necessary to verify the price rules with the customer data
                $billingAddress->setCustomerId($customerId);
            }
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
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress && $shippingAddress->getShippingMethod()) {
                $shippingRate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
                if ($shippingRate) {
                    $shippingAddress->setLimitCarrier($shippingRate->getCarrier());
                }
            }
        }
        $this->paymentMethodManagement->set($cartId, $paymentMethod);
        return true;
    }

    /**
     * Get Cart repository
     *
     * @return CartRepositoryInterface
     * @deprecated 100.2.0
     */
    private function getCartRepository()
    {
        if (!$this->cartRepository) {
            $this->cartRepository = ObjectManager::getInstance()
                ->get(CartRepositoryInterface::class);
        }
        return $this->cartRepository;
    }
}
