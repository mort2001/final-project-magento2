<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Override\Magento\Quote\Model;

use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteAddressValidator;
use Magento\Quote\Model\ShippingAddressAssignment;
use Psr\Log\LoggerInterface as Logger;
use Tigren\CustomAddress\Helper\Data as CustomAddressHelper;

/**
 * Class BillingAddressManagement
 * @package Tigren\CustomAddress\Model\Quote
 */
class BillingAddressManagement extends \Magento\Quote\Model\BillingAddressManagement
{
    /**
     * @var CustomAddressHelper
     */
    protected $customAddressHelper;

    /**
     * @var ShippingAddressAssignment
     */
    private $shippingAddressAssignment;

    /**
     * Constructs a quote billing address service object.
     *
     * @param CartRepositoryInterface $quoteRepository Quote repository.
     * @param QuoteAddressValidator $addressValidator Address validator.
     * @param Logger $logger Logger.
     * @param AddressRepositoryInterface $addressRepository .
     * @param CustomAddressHelper $customAddressHelper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        Logger $logger,
        AddressRepositoryInterface $addressRepository,
        CustomAddressHelper $customAddressHelper
    ) {
        parent::__construct($quoteRepository, $addressValidator, $logger, $addressRepository);
        $this->customAddressHelper = $customAddressHelper;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function assign($cartId, AddressInterface $address, $useForShipping = false)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->removeAddress($quote->getBillingAddress()->getId());
        $quote->setBillingAddress($address);

        $shippingAddressBeforeSave = $quote->getShippingAddress();
        $cityIdOld = $shippingAddressBeforeSave->getCityId();
        $subdistrictIdOld = $shippingAddressBeforeSave->getSubdistrictId();

        try {
            $this->getShippingAddressAssignment()->setAddress($quote, $address, $useForShipping);
            $quote->setDataChanges(true);
            $this->quoteRepository->save($quote);

            /** Save custom attributes billing address */
            $billingAddress = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();
            $extensionAttributes = $address->getExtensionAttributes();

            if ($extensionAttributes && $billingAddress->getId()) {
                $cityId = $extensionAttributes->getCityId();
                $subdistrictId = $extensionAttributes->getSubdistrictId();
                if ($cityId && $subdistrictId) {
                    $this->customAddressHelper->updateDataAddress($billingAddress, $cityId, $subdistrictId);
                    $billingAddress->save();
                    if ($useForShipping) {
                        $this->customAddressHelper->updateDataAddress($shippingAddress, $cityId, $subdistrictId);
                        $shippingAddress->save();
                    }
                }
            }

            // Save custom attributes to shipping address
            if (!$useForShipping && $cityIdOld && $subdistrictIdOld) {
                $this->customAddressHelper->updateDataAddress($shippingAddress, $cityIdOld, $subdistrictIdOld);
                $shippingAddress->save();
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Unable to save address. Please check input data.'));
        }
        return $quote->getBillingAddress()->getId();
    }

    /**
     * @return ShippingAddressAssignment
     * @deprecated 100.2.0
     */
    private function getShippingAddressAssignment()
    {
        if (!$this->shippingAddressAssignment) {
            $this->shippingAddressAssignment = ObjectManager::getInstance()
                ->get(ShippingAddressAssignment::class);
        }
        return $this->shippingAddressAssignment;
    }
}
