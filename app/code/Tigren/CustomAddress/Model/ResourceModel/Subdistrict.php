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
 * Class Subdistrict
 * @package Tigren\CustomAddress\Model\ResourceModel
 */
class Subdistrict extends AbstractDb
{
    /**
     * Table with localized subdistrict names
     *
     * @var string
     */
    protected $_subdistrictNameTable;

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
     * Loads subdistrict by subdistrict code and city id
     *
     * @param \Tigren\CustomAddress\Model\Subdistrict $subdistrict
     * @param string $subdistrictCode
     * @param string $city
     *
     * @return $this
     * @throws LocalizedException
     */
    public function loadByCode(\Tigren\CustomAddress\Model\Subdistrict $subdistrict, $subdistrictCode, $city)
    {
        return $this->_loadByCity($subdistrict, $city, (string)$subdistrictCode, 'code');
    }

    /**
     * Load object by city id and code or default name
     *
     * @param AbstractModel $object
     * @param int $city
     * @param string $value
     * @param string $field
     * @return $this
     * @throws LocalizedException
     */
    protected function _loadByCity($object, $city, $value, $field)
    {
        $connection = $this->getConnection();
        $locale = $this->_localeResolver->getLocale();
        $joinCondition = $connection->quoteInto(
            'rname.subdistrict_id = subdistrict.subdistrict_id AND rname.locale = ?',
            $locale
        );
        $select = $connection->select()->from(
            ['subdistrict' => $this->getMainTable()]
        )->joinLeft(
            ['rname' => $this->_subdistrictNameTable],
            $joinCondition,
            ['name']
        )->where(
            'subdistrict.city_id = ?',
            $city
        )->where(
            "subdistrict.{$field} = ?",
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
     * Load data by city id and default subdistrict name
     *
     * @param \Tigren\CustomAddress\Model\Subdistrict $subdistrict
     * @param string $subdistrictName
     * @param string $city
     * @return $this
     * @throws LocalizedException
     */
    public function loadByName(\Tigren\CustomAddress\Model\Subdistrict $subdistrict, $subdistrictName, $city)
    {
        return $this->_loadByCity($subdistrict, $city, (string)$subdistrictName, 'default_name');
    }

    /**
     * Define main and locale subdistrict name tables
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('directory_city_subdistrict', 'subdistrict_id');
        $this->_subdistrictNameTable = $this->getTable('directory_city_subdistrict_name');
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

        $subdistrictField = $connection->quoteIdentifier($this->getMainTable() . '.' . $this->getIdFieldName());

        $condition = $connection->quoteInto('lrn.locale = ?', $locale);
        $select->joinLeft(
            ['lrn' => $this->_subdistrictNameTable],
            "{$subdistrictField} = lrn.subdistrict_id AND {$condition}",
            []
        );

        if ($locale != $systemLocale) {
            $nameExpr = $connection->getCheckSql('lrn.subdistrict_id is null', 'srn.name', 'lrn.name');
            $condition = $connection->quoteInto('srn.locale = ?', $systemLocale);
            $select->joinLeft(
                ['srn' => $this->_subdistrictNameTable],
                "{$subdistrictField} = srn.subdistrict_id AND {$condition}",
                ['name' => $nameExpr]
            );
        } else {
            $select->columns(['name'], 'lrn');
        }

        return $select;
    }
}
