<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin;

use Magento\Customer\Api\Data\AddressExtension;
use Magento\Customer\Api\Data\AddressInterface as Subject;
use Magento\Framework\Api\ExtensibleDataInterface;
use Tigren\CustomAddress\Model\Address\AdditionalAttributes as AdditionalAttributes;
use Tigren\CustomAddress\Model\Address\AdditionalAttributesFactory as AdditionalAttributesFactory;

/**
 * Class FixGetExtensionAttributes
 *
 * @package Tigren\CustomAddress\Plugin
 */
class FixGetExtensionAttributes implements ExtensibleDataInterface
{
    /**
     * @var AdditionalAttributesFactory
     */
    protected $extensionAttributeFactory;

    /**
     * FixGetExtensionAttributes constructor.
     *
     * @param AdditionalAttributesFactory $extensionAttributeFactory
     */
    public function __construct(AdditionalAttributesFactory $extensionAttributeFactory)
    {
        $this->extensionAttributeFactory = $extensionAttributeFactory;
    }

    /**
     * @param Subject $subject
     * @param AdditionalAttributes|AddressExtension|null $extensionAttributes
     *
     * @return AdditionalAttributes
     */
    public function afterGetExtensionAttributes(
        Subject $subject,
        $extensionAttributes = null
    ) {
        if ($extensionAttributes !== null) {
            return $extensionAttributes;
        }

        $extensionAttributes = $this->extensionAttributeFactory->create();

        $extensionAttributes->setCityId($subject->getCityId());
        $extensionAttributes->setSubdistrict($subject->getSubdistrict());
        $extensionAttributes->setSubdistrictId($subject->getSubdistrictId());

        return $extensionAttributes;
    }
}
