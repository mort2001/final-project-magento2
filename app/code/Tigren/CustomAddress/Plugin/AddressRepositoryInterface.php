<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Plugin;

use Magento\Customer\Api\AddressRepositoryInterface as Subject;
use Magento\Customer\Api\Data\AddressInterface as Entity;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Logger\Monolog;

/**
 * Class AddressRepositoryInterface
 *
 * @package Tigren\CustomAddress\Plugin
 */
class AddressRepositoryInterface
{
    /**
     * @var RequestInterface
     */
    protected $httpRequest;

    /**
     * @var Monolog
     */
    protected $logger;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * AddressRepositoryInterface constructor.
     * @param RequestInterface $httpRequest
     * @param Monolog $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        RequestInterface $httpRequest,
        Monolog $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->httpRequest = $httpRequest;
        $this->logger = $logger;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @param Subject $subject
     * @param Entity $entity
     * @param $addressId
     * @return Entity
     */
    public function afterGetById(Subject $subject, Entity $entity, $addressId)
    {
        $extensionAttributes = $entity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            return $entity;
        }

        $additionAddressDataSelect = $this->connection->select()
            ->from(
                ['cae' => $this->connection->getTableName('customer_address_entity')],
                ['city_id', 'subdistrict', 'subdistrict_id']
            )
            ->where('entity_id = ?', $addressId);
        $additionAddressData = $this->connection->fetchRow($additionAddressDataSelect);

        if (!empty($additionAddressData)) {
            $extensionAttributes->setCityId($additionAddressData['city_id']);
            $extensionAttributes->setSubdistrict($additionAddressData['subdistrict']);
            $extensionAttributes->setSubdistrictId($additionAddressData['subdistrict_id']);
        } else {
            $extensionAttributes->setCityId('');
            $extensionAttributes->setSubdistrictId('');
            $extensionAttributes->setSubdistrict('');
        }

        $entity->setExtensionAttributes($extensionAttributes);

        return $entity;
    }

    /**
     * @param Subject $subject
     * @param Entity $entity
     *
     * @return array [Entity]
     */
    public function beforeSave(Subject $subject, Entity $entity)
    {
        $extensionAttributes = $entity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            return [$entity];
        }

        $customAttributes = [
            'city_id' => $extensionAttributes->getCityId() ?: $this->httpRequest->getParam('city_id'),
            'subdistrict' => $extensionAttributes->getSubdistrict() ?: $this->httpRequest->getParam('subdistrict'),
            'subdistrict_id' => $extensionAttributes->getSubdistrictId() ?: $this->httpRequest->getParam('subdistrict_id')
        ];

        foreach ($customAttributes as $attributeCode => $attributeValue) {
            $entity->setCustomAttribute($attributeCode, $attributeValue);
        }

        return [$entity];
    }
}
