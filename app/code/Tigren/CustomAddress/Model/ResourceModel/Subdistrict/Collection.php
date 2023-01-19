<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\ResourceModel\Subdistrict;

use Magento\Framework\Api\Search\AggregationInterface;
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
class Collection extends AbstractCollection implements \Magento\Framework\Api\Search\SearchResultInterface
{
    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    protected $_map = [
        'fields' => [
            'subdistrict_id' => 'main_table.subdistrict_id'
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
            'zipcode' => 'zipcode',
            'country_id' => 'country_id',
        ];

        foreach ($this as $item) {
            $option = [];
            foreach ($propertyMap as $code => $field) {
                $option[$code] = $item->getData($field);
            }
//            $option['label'] = $item->getName(); //This takes Default name
            $option['label'] = $item->getSubdistrictname(); //This takes the name in directory_city_subdistrict_name table based on __initSelect() function below
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
        $this->_cityNameTable = $this->getTable('directory_region_city_name');
        $this->_regionTable = $this->getTable('directory_country_region');
        $this->_regionNameTable = $this->getTable('directory_country_region_name');
        $this->_subdistrictNameTable = $this->getTable('directory_city_subdistrict_name');

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
        $this->getSelect()->from(['main_table' => $this->getMainTable()], ['*'])
            ->joinLeft(
                ['subdistrictName' => $this->_subdistrictNameTable],
                $this->getConnection()->quoteInto('main_table.subdistrict_id = subdistrictName.subdistrict_id AND subdistrictName.locale = ?', $locale),
                ['subdistrictname' => 'subdistrictName.name']
            )->joinLeft(
                ['cityName' => $this->_cityNameTable],
                "main_table.city_id = cityName.city_id AND cityName.locale = '{$locale}'",
                ['cityname' => 'cityName.name']
            )->joinLeft(
                ['city' => $this->_cityTable],
                'main_table.city_id = city.city_id',
                ['region_id' => 'city.region_id','city_code' => 'city.code']
            )->joinLeft(
                ['region' => $this->_regionTable],
                'city.region_id = region.region_id',
                ['country_id' => 'region.country_id']
            )->joinLeft(
                ['regionName' => $this->_regionNameTable],
                "region.region_id = regionName.region_id AND regionName.locale = '{$locale}'",
                ['regionname' => 'regionName.name']
            );

        return $this;
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
