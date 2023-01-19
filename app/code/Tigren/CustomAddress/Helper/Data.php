<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Helper;

use Magento\Customer\Model\AddressFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Profiler;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\CustomAddress\Model\City;
use Tigren\CustomAddress\Model\CityFactory;
use Tigren\CustomAddress\Model\Config\Source\SuggestionType;
use Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory;
use Tigren\CustomAddress\Model\SubdistrictFactory;

/**
 * Class Data
 * @package Tigren\CustomAddress\Helper
 */
class Data extends \Magento\Directory\Helper\Data
{
    /**
     * Suggestion type configuration path
     */
    const XML_PATH_SUGGESTION_TYPE = 'custom_address/general/suggestion_type';

    /**
     * Move billing configuration path
     */
    const XML_PATH_MOVE_BILLING = 'custom_address/general/move_billing';

    /**
     * Enable full tax invoice configuration path
     */
    const XML_PATH_FULL_TAX_INVOICE_ENABLED = 'custom_address/general/full_tax_invoice_enabled';

    /**
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * Json representation of cities data
     *
     * @var string
     */
    protected $_cityJson;

    /**
     * @var CountryFactory
     */
    protected $_countryFactory;

    /**
     * Region collection
     *
     * @var \Tigren\CustomAddress\Model\ResourceModel\City\Collection
     */
    protected $_cityCollection;

    /**
     * Json representation of subdistricts data
     *
     * @var string
     */
    protected $_subdistrictJson;

    /**
     * Region collection
     *
     * @var \Tigren\CustomAddress\Model\ResourceModel\Subdistrict\Collection
     */
    protected $_subdistrictCollection;

    /**
     * @var CityFactory
     */
    protected $cityFactory;

    /**
     * @var SubdistrictFactory
     */
    protected $subdistrictFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $regionFactory;

    /**
     * Currently selected store ID if applicable
     *
     * @var int
     */
    protected $_storeId;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var AddressFactory
     */
    protected $_customerAddress;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var
     */
    protected $_cityNameCache;

    /**
     * @var
     */
    protected $_subdistrictNameCache;

    /**
     * Data constructor.
     * @param Context $context
     * @param Config $configCacheType
     * @param CountryFactory $countryFactory
     * @param Collection $countryCollection
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regCollectionFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     * @param \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollectionFactory
     * @param CollectionFactory $subdistrictCollectionFactory
     * @param CityFactory $cityFactory
     * @param SubdistrictFactory $subdistrictFactory
     * @param RegionFactory $regionFactory
     * @param RequestInterface $request
     * @param AddressFactory $customerAddress
     * @param ResolverInterface $localeResolver
     * @param ResourceConnection $resource
     * @param CacheInterface $cache
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        Config $configCacheType,
        CountryFactory $countryFactory,
        Collection $countryCollection,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regCollectionFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        \Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory $cityCollectionFactory,
        CollectionFactory $subdistrictCollectionFactory,
        CityFactory $cityFactory,
        SubdistrictFactory $subdistrictFactory,
        RegionFactory $regionFactory,
        RequestInterface $request,
        AddressFactory $customerAddress,
        ResolverInterface $localeResolver,
        ResourceConnection $resource,
        CacheInterface $cache
    ) {
        parent::__construct(
            $context,
            $configCacheType,
            $countryCollection,
            $regCollectionFactory,
            $jsonHelper,
            $storeManager,
            $currencyFactory
        );

        $this->_countryFactory = $countryFactory;
        $this->_cityCollection = $cityCollectionFactory;
        $this->_subdistrictCollection = $subdistrictCollectionFactory;
        $this->cityFactory = $cityFactory;
        $this->subdistrictFactory = $subdistrictFactory;
        $this->regionFactory = $regionFactory;
        $this->_request = $request;
        $this->_customerAddress = $customerAddress;
        $this->resource = $resource;
        $this->_localeResolver = $localeResolver;
        $this->_cache = $cache;

        $this->setStoreId($this->getCurrentStoreId());
    }

    /**
     * Set a specified store ID value
     *
     * @param int $store
     * @return $this
     */
    public function setStoreId($store)
    {
        $this->_storeId = $store;
        return $this;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }

    /**
     * @param $cityId
     * @return false|mixed
     */
    public function getCityNameById($cityId)
    {
        if (empty($cityId)) {
            return false;
        }
        return @$this->getCityNames()[$cityId] ?: false;
    }

    /**
     * @return mixed
     */
    public function getCityNames()
    {
        if (empty($this->_cityNameCache)) {
            $localeCode = $this->_localeResolver->getLocale();
            $cachekey = 'DIRECTORY_CITY_NAME_' . $localeCode;
            if (($data = $this->_cache->load($cachekey)) !== false) {
                $this->_cityNameCache = unserialize($data);
            } else {
                $connection = $this->resource->getConnection();
                $sql = "SELECT d.city_id,
                    IFNULL(dn.name, CASE WHEN 'th_TH' = '{$localeCode}' THEN d.default_name ELSE d.code END) name
                    from directory_region_city d
                    left join directory_region_city_name dn on d.city_id = dn.city_id
                    where dn.locale = :locale;";
                $res = $connection->fetchAssoc($sql, ['locale' => $localeCode]);
                foreach ($res as $id => $data) {
                    $this->_cityNameCache[$id] = $data['name'];
                }
                // keep cache 10 days
                $this->_cache->save(
                    serialize($this->_cityNameCache),
                    $cachekey,
                    [\Magento\Framework\App\Cache\Type\Collection::CACHE_TAG],
                    864000
                );
            }
        }
        return $this->_cityNameCache;
    }

    /**
     * @param $subdistrictId
     * @return false|mixed
     */
    public function getSubdistrictNameById($subdistrictId)
    {
        if (empty($subdistrictId)) {
            return false;
        }
        return @$this->getSubdistrictNames()[$subdistrictId] ?: false;
    }

    /**
     * @return mixed
     */
    public function getSubdistrictNames()
    {
        if (empty($this->_subdistrictNameCache)) {
            $localeCode = $this->_localeResolver->getLocale();
            $cachekey = 'DIRECTORY_SUBDISTRICT_NAME_' . $localeCode;
            if (($data = $this->_cache->load($cachekey)) !== false) {
                $this->_subdistrictNameCache = unserialize($data);
            } else {
                $connection = $this->resource->getConnection();
                $sql = "SELECT d.subdistrict_id,
                    IFNULL(dn.name, CASE WHEN 'th_TH' = '{$localeCode}' THEN d.default_name ELSE d.code END) name
                    from directory_city_subdistrict d
                    join directory_city_subdistrict_name dn on d.subdistrict_id = dn.subdistrict_id
                    where dn.locale = :locale;";
                $res = $connection->fetchAssoc($sql, ['locale' => $localeCode]);
                foreach ($res as $id => $data) {
                    $this->_subdistrictNameCache[$id] = $data['name'];
                }
                // keep cache 10 days
                $this->_cache->save(
                    serialize($this->_subdistrictNameCache),
                    $cachekey,
                    [\Magento\Framework\App\Cache\Type\Collection::CACHE_TAG],
                    864000
                );
            }
        }
        return $this->_subdistrictNameCache;
    }

    /**
     * Get Suggestion Type of Address
     *
     **/
    public function getSuggestionType()
    {
        $suggestionType = (int)$this->getScopeConfig(self::XML_PATH_SUGGESTION_TYPE);

        return $suggestionType
            ? $suggestionType
            : SuggestionType::SUGGESTION_TYPE_AUTO_COMPLETE;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getScopeConfig($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->_storeId);
    }

    /**
     * Get Move Billing At Checkout Page
     *
     **/
    public function getMoveBilling()
    {
        return (int)$this->getScopeConfig(self::XML_PATH_MOVE_BILLING);
    }

    /**
     * @return int
     */
    public function isFullTaxInvoiceEnabled()
    {
        return (int)$this->getScopeConfig(self::XML_PATH_FULL_TAX_INVOICE_ENABLED);
    }

    /**
     * Retrieve cities data json
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCityJson()
    {
        Profiler::start('TEST: ' . __METHOD__, ['group' => 'TEST', 'method' => __METHOD__]);

        if (!$this->_cityJson) {
            $cacheKey = 'DIRECTORY_CITIES_JSON_STORE' . $this->_storeManager->getStore()->getId();
            $json = $this->_configCacheType->load($cacheKey);
            if (empty($json)) {
                $cities = $this->getCityData();
                $json = $this->jsonHelper->jsonEncode($cities);
                if ($json === false) {
                    $json = 'false';
                }
                $this->_configCacheType->save($json, $cacheKey);
            }
            $this->_cityJson = $json;
        }

        Profiler::stop('TEST: ' . __METHOD__);

        return $this->_cityJson;
    }

    /**
     * Retrieve cities data
     *
     * @return array
     */
    public function getCityData()
    {
        $collection = $this->_cityCollection->create();

        $cities = [
            'config' => [
                'show_all_cities' => true
            ]
        ];

        foreach ($collection as $city) {
            /** @var $city City */
            if (!$city->getCityId()) {
                continue;
            }
            $cities[$city->getRegionId()][$city->getCityId()] = [
                'code' => $city->getCode(),
                'name' => (string)__($city->getName()),
            ];
        }

        return $cities;
    }

    /**
     * Retrieve subdistricts data json
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSubdistrictJson()
    {
        Profiler::start('TEST: ' . __METHOD__, ['group' => 'TEST', 'method' => __METHOD__]);

        if (!$this->_subdistrictJson) {
            $cacheKey = 'DIRECTORY_SUBDISTRICTS_JSON_STORE' . $this->_storeManager->getStore()->getId();
            $json = $this->_configCacheType->load($cacheKey);
            if (empty($json)) {
                $subdistricts = $this->getSubdistrictData();
                $json = $this->jsonHelper->jsonEncode($subdistricts);
                if ($json === false) {
                    $json = 'false';
                }
                $this->_configCacheType->save($json, $cacheKey);
            }
            $this->_subdistrictJson = $json;
        }

        Profiler::stop('TEST: ' . __METHOD__);

        return $this->_subdistrictJson;
    }

    /**
     * Retrieve subdistrict data
     *
     * @return array
     */
    public function getSubdistrictData()
    {
        $collection = $this->_subdistrictCollection->create();

        $subdistricts = [
            'config' => [
                'show_all_subdistricts' => true
            ]
        ];

        foreach ($collection as $subdistrict) {
            /** @var $subdistrict */
            if (!$subdistrict->getSubdistrictId()) {
                continue;
            }
            $subdistricts[$subdistrict->getCityId()][$subdistrict->getSubdistrictId()] = [
                'code' => $subdistrict->getCode(),
                'name' => (string)__($subdistrict->getName()),
                'zipcode' => $subdistrict->getZipcode()
            ];
        }

        return $subdistricts;
    }

    /**
     * @param $cityId
     * @param string $locale
     * @return string
     */
    public function getCityById($cityId, $locale = '')
    {
        if (empty($cityId)) {
            return '';
        }

        $connection = $this->getConnection();

        if (!$locale) {
            $locale = $this->_localeResolver->getLocale();
        }

        $city = $this->getConnection()->select()->from($connection->getTableName('directory_region_city_name'), 'name')
            ->where('city_id = ?', $cityId)
            ->where('locale = ?', $locale);

        return $this->getConnection()->fetchOne($city);
    }

    /**
     * @return false|AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }

    /**
     * @param $subdistrictId
     * @return string
     */
    public function getSubdistrictById($subdistrictId, $locale = '')
    {
        if (empty($subdistrictId)) {
            return '';
        }

        $connection = $this->getConnection();

        if (!$locale) {
            $locale = $this->_localeResolver->getLocale();
        }

        $subdistrict = $this->getConnection()->select()->from(
            $connection->getTableName('directory_city_subdistrict_name'),
            'name'
        )
            ->where('subdistrict_id = ?', $subdistrictId)
            ->where('locale = ?', $locale);

        return $this->getConnection()->fetchOne($subdistrict);
    }

    /**
     * @param $countryId
     * @param string $locale
     * @return string
     */
    public function getCountryById($countryId, $locale = '')
    {
        if (empty($countryId)) {
            return '';
        }

        if (!$locale) {
            $locale = $this->_localeResolver->getLocale();
        }

        return $this->_countryFactory->create()->loadByCode($countryId)->getName($locale);
    }

    /**
     * @param $regionId
     * @param string $locale
     * @return string
     */
    public function getRegionById($regionId, $locale = '')
    {
        if (empty($regionId)) {
            return '';
        }

        $connection = $this->getConnection();

        if (!$locale) {
            $locale = $this->_localeResolver->getLocale();
        }

        $region = $this->getConnection()->select()->from(
            $connection->getTableName('directory_country_region_name'),
            'name'
        )
            ->where('region_id = ?', $regionId)
            ->where('locale = ?', $locale);

        return $this->getConnection()->fetchOne($region);
    }

    /**
     * @return int
     */
    public function getCurrentCityId()
    {
        $address = $this->getCurrentAddress();
        return $address ? $address->getCityId() : 0;
    }

    /**
     * @return null
     */
    protected function getCurrentAddress()
    {
        $addressId = $this->_request->getParam('id');
        if (!$addressId) {
            return null;
        }
        $address = $this->_customerAddress->create()->load($addressId);
        if (!$address->getId()) {
            return null;
        }
        return $address;
    }

    /**
     * @return int
     */
    public function getCurrentSubdistrictId()
    {
        $address = $this->getCurrentAddress();
        return $address ? $address->getSubdistrictId() : 0;
    }

    /**
     * @param $address
     * @param $cityId
     * @param $subdistrictId
     */
    public function updateDataAddress(&$address, $cityId, $subdistrictId)
    {
        $newCity = $this->cityFactory->create()->load($cityId);
        $city = $newCity->getName();
        $newSubdistrict = $this->subdistrictFactory->create()->load($subdistrictId);
        $subdistrict = $newSubdistrict->getName();

        $address->setCityId($cityId);
        $address->setCity($city);
        $address->setSubdistrictId($subdistrictId);
        $address->setSubdistrict($subdistrict);

        if ($address->getExtensionAttributes()) {
            $customAttributes = [
                'city_id' => $cityId,
                'subdistrict' => $subdistrict,
                'subdistrict_id' => $subdistrictId
            ];

            foreach ($customAttributes as $attributeCode => $attributeValue) {
                $address->setCustomAttribute($attributeCode, $attributeValue);
            }
        }
    }
}
