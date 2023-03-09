<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\ResourceModel\Subdistrict;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;
use Tigren\CustomAddress\Model\ResourceModel\Subdistrict;

/**
 * Class Collection
 * @package Tigren\CustomAddress\Model\ResourceModel\Subdistrict
 */
class Collection extends AbstractCollection
{
    /**
     * Locale subdistrict name table name
     *
     * @var string
     */
    protected $_subdistrictNameTable;

    /**
     * Country table name
     *
     * @var string
     */
    protected $_cityTable;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param ResolverInterface $localeResolver
     * @param mixed $connection
     * @param AbstractDb $resource
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ResolverInterface $localeResolver,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_localeResolver = $localeResolver;
        $this->_resource = $resource;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Filter by city_id
     *
     * @param string|array $cityId
     * @return $this
     */
    public function addCityFilter($cityId)
    {
        if (!empty($cityId)) {
            if (is_array($cityId)) {
                $this->addFieldToFilter('main_table.city_id', ['in' => $cityId]);
            } else {
                $this->addFieldToFilter('main_table.city_id', $cityId);
            }
        }
        return $this;
    }

    /**
     * Filter by subdistrict code
     *
     * @param string|array $subdistrictCode
     * @return $this
     */
    public function addSubdistrictCodeFilter($subdistrictCode)
    {
        if (!empty($subdistrictCode)) {
            if (is_array($subdistrictCode)) {
                $this->addFieldToFilter('main_table.code', ['in' => $subdistrictCode]);
            } else {
                $this->addFieldToFilter('main_table.code', $subdistrictCode);
            }
        }
        return $this;
    }

    /**
     * Filter by subdistrict name
     *
     * @param string|array $subdistrictName
     * @return $this
     */
    public function addSubdistrictNameFilter($subdistrictName)
    {
        if (!empty($subdistrictName)) {
            if (is_array($subdistrictName)) {
                $this->addFieldToFilter('main_table.default_name', ['in' => $subdistrictName]);
            } else {
                $this->addFieldToFilter('main_table.default_name', $subdistrictName);
            }
        }
        return $this;
    }

    /**
     * Filter subdistrict by its code or name
     *
     * @param string|array $subdistrict
     * @return $this
     */
    public function addSubdistrictCodeOrNameFilter($subdistrict)
    {
        if (!empty($subdistrict)) {
            $condition = is_array($subdistrict) ? ['in' => $subdistrict] : $subdistrict;
            $this->addFieldToFilter(
                ['main_table.code', 'main_table.default_name'],
                [$condition, $condition]
            );
        }
        return $this;
    }

    /**
     * Convert collection items to select options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $propertyMap = [
            'value' => 'subdistrict_id',
            'title' => 'default_name',
            'city_id' => 'city_id',
            'zipcode' => 'zipcode'
        ];

        foreach ($this as $item) {
            $option = [];
            foreach ($propertyMap as $code => $field) {
                $option[$code] = $item->getData($field);
            }
            $option['label'] = $item->getName();
            $options[] = $option;
        }

        if (count($options) > 0) {
            array_unshift(
                $options,
                ['title' => '', 'value' => '', 'label' => __('Please select subdistrict.')]
            );
        }
        return $options;
    }

    /**
     * Define main, city, locale subdistrict name tables
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Tigren\CustomAddress\Model\Subdistrict::class,
            Subdistrict::class
        );

        $this->_cityTable = $this->getTable('directory_region_city');
        $this->_subdistrictNameTable = $this->getTable('directory_city_subdistrict_name');

        $this->addOrder('name', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $this->addOrder('default_name', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
    }

    /**
     * Initialize select object
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $locale = $this->_localeResolver->getLocale();

        $this->addBindParam(':subdistrict_locale', $locale);
        $this->getSelect()->joinLeft(
            ['rname' => $this->_subdistrictNameTable],
            'main_table.subdistrict_id = rname.subdistrict_id AND rname.locale = :subdistrict_locale',
            ['name']
        );

        return $this;
    }
}
