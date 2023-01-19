<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\ResourceModel;

use Magento\Framework\AppInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class City
 * @package Tigren\CustomAddress\Model\ResourceModel
 */
class City extends AbstractDb
{
    /**
     * Table with localized city names
     *
     * @var string
     */
    protected $_cityNameTable;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @param Context $context
     * @param ResolverInterface $localeResolver
     * @param string $connectionName
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_localeResolver = $localeResolver;
    }

    /**
     * Loads city by city code and region id
     *
     * @param \Tigren\CustomAddress\Model\City $city
     * @param string $cityCode
     * @param string $region
     *
     * @return $this
     * @throws LocalizedException
     */
    public function loadByCode(\Tigren\CustomAddress\Model\City $city, $cityCode, $region)
    {
        return $this->_loadByRegion($city, $region, (string)$cityCode, 'code');
    }

    /**
     * Load object by region id and code or default name
     *
     * @param AbstractModel $object
     * @param int $region
     * @param string $value
     * @param string $field
     * @return $this
     * @throws LocalizedException
     */
    protected function _loadByRegion($object, $region, $value, $field)
    {
        $connection = $this->getConnection();
        $locale = $this->_localeResolver->getLocale();
        $joinCondition = $connection->quoteInto('rname.city_id = city.city_id AND rname.locale = ?', $locale);
        $select = $connection->select()->from(
            ['city' => $this->getMainTable()]
        )->joinLeft(
            ['rname' => $this->_cityNameTable],
            $joinCondition,
            ['name']
        )->where(
            'city.region_id = ?',
            $region
        )->where(
            "city.{$field} = ?",
            $value
        );

        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }

        $this->_afterLoad($object);

        return $this;
    }

    /**
     * Load data by region id and default city name
     *
     * @param \Tigren\CustomAddress\Model\City $city
     * @param string $cityName
     * @param string $region
     * @return $this
     * @throws LocalizedException
     */
    public function loadByName(\Tigren\CustomAddress\Model\City $city, $cityName, $region)
    {
        return $this->_loadByRegion($city, $region, (string)$cityName, 'default_name');
    }

    /**
     * Define main and locale region name tables
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('directory_region_city', 'city_id');
        $this->_cityNameTable = $this->getTable('directory_region_city_name');
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @param AbstractModel $object
     * @return Select
     * @throws LocalizedException
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        $connection = $this->getConnection();

        $locale = $this->_localeResolver->getLocale();
        $systemLocale = AppInterface::DISTRO_LOCALE_CODE;

        $cityField = $connection->quoteIdentifier($this->getMainTable() . '.' . $this->getIdFieldName());

        $condition = $connection->quoteInto('lrn.locale = ?', $locale);
        $select->joinLeft(
            ['lrn' => $this->_cityNameTable],
            "{$cityField} = lrn.city_id AND {$condition}",
            []
        );

        if ($locale != $systemLocale) {
            $nameExpr = $connection->getCheckSql('lrn.city_id is null', 'srn.name', 'lrn.name');
            $condition = $connection->quoteInto('srn.locale = ?', $systemLocale);
            $select->joinLeft(
                ['srn' => $this->_cityNameTable],
                "{$cityField} = srn.city_id AND {$condition}",
                ['name' => $nameExpr]
            );
        } else {
            $select->columns(['name'], 'lrn');
        }

        return $select;
    }
}
