<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\ResourceModel\City;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;
use Tigren\CustomAddress\Model\ResourceModel\City;

/**
 * Class Collection
 * @package Tigren\CustomAddress\Model\ResourceModel\City
 */
class Collection extends AbstractCollection implements \Magento\Framework\Api\Search\SearchResultInterface
{
    /**
     * Locale region name table name
     *
     * @var string
     */
    protected $_cityNameTable;

    /**
     * Region table name
     *
     * @var string
     */
    protected $_regionTable;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    protected $_map = [
        'fields' => [
            'city_id' => 'main_table.city_id'
        ]
    ];

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
        EntityFactory          $entityFactory,
        LoggerInterface        $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface       $eventManager,
        ResolverInterface      $localeResolver,
        AdapterInterface       $connection = null,
        AbstractDb             $resource = null
    ) {
        $this->_localeResolver = $localeResolver;
        $this->_resource = $resource;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Filter by region_id
     *
     * @param string|array $regionId
     * @return $this
     */
    public function addRegionFilter($regionId)
    {
        if (!empty($regionId)) {
            if (is_array($regionId)) {
                $this->addFieldToFilter('main_table.region_id', ['in' => $regionId]);
            } else {
                $this->addFieldToFilter('main_table.region_id', $regionId);
            }
        }
        return $this;
    }

    /**
     * Filter by city code
     *
     * @param string|array $cityCode
     * @return $this
     */
    public function addCityCodeFilter($cityCode)
    {
        if (!empty($cityCode)) {
            if (is_array($cityCode)) {
                $this->addFieldToFilter('main_table.code', ['in' => $cityCode]);
            } else {
                $this->addFieldToFilter('main_table.code', $cityCode);
            }
        }
        return $this;
    }

    /**
     * Filter by city name
     *
     * @param string|array $cityName
     * @return $this
     */
    public function addCityNameFilter($cityName)
    {
        if (!empty($cityName)) {
            if (is_array($cityName)) {
                $this->addFieldToFilter('main_table.default_name', ['in' => $cityName]);
            } else {
                $this->addFieldToFilter('main_table.default_name', $cityName);
            }
        }
        return $this;
    }

    /**
     * Filter city by its code or name
     *
     * @param string|array $city
     * @return $this
     */
    public function addCityCodeOrNameFilter($city)
    {
        if (!empty($city)) {
            $condition = is_array($city) ? ['in' => $city] : $city;
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
            'value' => 'city_id',
            'title' => 'default_name',
            'region_id' => 'region_id',
            'country_id' => 'country_id',
        ];

        foreach ($this as $item) {
            $option = [];
            foreach ($propertyMap as $code => $field) {
                $option[$code] = $item->getData($field);
            }
            $option['label'] = $item->getCityname(); //This takes the name in directory_region_city_name table based on __initSelect() function below
            $options[] = $option;
        }

        if (count($options) > 0) {
            array_unshift(
                $options,
                ['title' => '', 'value' => '', 'label' => __('Please select city.')]
            );
        }

        return $options;
    }

    /**
     * Define main, region, locale city name tables
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Tigren\CustomAddress\Model\City::class,
            City::class
        );

        $this->_regionTable = $this->getTable('directory_country_region');
        $this->_cityNameTable = $this->getTable('directory_region_city_name');
        $this->_regionNameTable = $this->getTable('directory_country_region_name');

//        $this->addOrder('name', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
//        $this->addOrder('default_name', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
    }

    /**
     * Initialize select object
     *
     * @return $this
     */
    protected function _initSelect()
    {
//        parent::_initSelect();
        $locale = $this->_localeResolver->getLocale();
        $this->getSelect()->reset();
        $this->getSelect()->from(['main_table' => $this->getMainTable()], ['*'])->joinLeft(
            ['cityName' => $this->_cityNameTable],
            $this->getConnection()->quoteInto('main_table.city_id = cityName.city_id AND cityName.locale = ?', $locale),
            ['cityname' => 'cityName.name']
        )->joinLeft(
            ['region' => $this->_regionTable],
            'main_table.region_id = region.region_id',
            ['regioncode' => 'region.code', 'country_id' => 'region.country_id']
        )->joinLeft(
            ['regionName' => $this->_regionNameTable],
            $this->getConnection()->quoteInto('main_table.region_id = regionName.region_id AND regionName.locale = ?', $locale),
            ['regionname' => 'regionName.name']
        );
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return SearchCriteriaInterface
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * Set search criteria.
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria($searchCriteria = null)
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * @param array|null $items
     * @return $this|SearchResultInterface
     * @throws \Exception
     */
    public function setItems(array $items = null)
    {
        if ($items) {
            foreach ($items as $item) {
                $this->addItem($item);
            }
        }
        return $this;
    }
}
