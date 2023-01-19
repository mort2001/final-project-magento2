<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Core\Helper;

use DateTime;
use DateTimeInterface;
use Exception;
use IntlDateFormatter;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Config;
use Magento\Checkout\Model\Session;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Integration\Model\Oauth\Token as TokenModel;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package Tigren\Core\Helper
 */
class Data extends AbstractHelper
{
    /**
     *
     */
    const XML_PATH_PWA_FEATUERE_PRODUCT_CONDITION = 'pwa_connector/general/feature_product';

    /**
     *
     */
    const DEFAULT_GROUP = 'design/head/';

    /**
     *
     * /**
     * @var TokenCollectionFactory
     */
    protected $tokenModelCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var
     */
    protected $_baseUrl;

    /**
     * @var TokenModelFactory
     */
    protected $tokenModelFactory;

    /**
     * @var TokenModel
     */
    protected $tokenModel;

    /**
     * @var
     */
    protected $_customerSession;

    /**
     * @var Config
     */
    protected $_catalogConfig;

    /**
     * @var Emulation
     */
    protected $_appEmulation;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Directory\Block\Data
     */
    protected $blockDirectory;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $helperDirectory;

    /**
     * @var ImageFactory
     */
    protected $imageHelperFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *  * @var Attribute
     *  */
    protected $_eavAttribute;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var AdapterInterface
     */
    protected $_connection;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param TokenModelFactory $tokenModelFactory
     * @param TokenModel $tokenModel
     * @param Config $catalogConfig
     * @param Emulation $appEmulation
     * @param LayoutFactory $layoutFactory
     * @param ImageFactory $imageHelperFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Directory\Block\Data $blockDirectory
     * @param \Magento\Directory\Helper\Data $helperDirectory
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param QuoteFactory $quoteFactory
     * @param Attribute $eavAttribute
     * @param ResourceConnection $resource
     * @param TimezoneInterface $localeDate
     * @param CustomerFactory $customerFactory
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        TokenCollectionFactory $tokenModelCollectionFactory,
        TokenModelFactory $tokenModelFactory,
        TokenModel $tokenModel,
        Config $catalogConfig,
        Emulation $appEmulation,
        LayoutFactory $layoutFactory,
        ImageFactory $imageHelperFactory,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Directory\Block\Data $blockDirectory,
        \Magento\Directory\Helper\Data $helperDirectory,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        QuoteFactory $quoteFactory,
        Attribute $eavAttribute,
        ResourceConnection $resource,
        TimezoneInterface $localeDate,
        CustomerFactory $customerFactory
    ) {
        $this->tokenModelCollectionFactory = $tokenModelCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        $this->tokenModelFactory = $tokenModelFactory;
        $this->tokenModel = $tokenModel;
        $this->_catalogConfig = $catalogConfig;
        $this->_appEmulation = $appEmulation;
        $this->layoutFactory = $layoutFactory;
        $this->imageHelperFactory = $imageHelperFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->blockDirectory = $blockDirectory;
        $this->helperDirectory = $helperDirectory;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->_quoteFactory = $quoteFactory;
        $this->_eavAttribute = $eavAttribute;
        $this->_resource = $resource;
        $this->_connection = $this->_resource->getConnection('core_write');
        $this->_localeDate = $localeDate;
        $this->_customerFactory = $customerFactory;
        parent::__construct($context);
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->getStore()->getId();
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * @param $id
     * @return string|null
     */
    public function getCustomerTokenById($id)
    {
        $token = $this->tokenModel->loadByCustomerId($id);
        if ($token->getId()) {
            return $token->getToken();
        } else {
            return $this->tokenModel->createCustomerToken($id)->getToken();
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @return Emulation
     */
    public function getAppEmulation()
    {
        return $this->_appEmulation;
    }

    /**
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomer()
    {
        return $this->customerRepository->getById($this->getCustomerId());
    }

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @return array
     */
    public function getCountryOptions()
    {
        $options = $this->blockDirectory->getCountryCollection()
            ->setForegroundCountries($this->blockDirectory->getTopDestinations())
            ->toOptionArray();
        return $options;
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @param null $date
     * @param int $format
     * @param bool $showTime
     * @param null $timezone
     * @return string
     * @throws Exception
     */
    public function formatDate(
        $date = null,
        $format = IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    ) {
        $date = $date instanceof DateTimeInterface ? $date : new DateTime($date);
        return $this->_localeDate->formatDateTime(
            $date,
            $format,
            $showTime ? $format : IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteId()
    {
        return $this->getStore()->getWebsiteId();
    }

    /**
     * @return mixed
     */
    public function getConditionFeature()
    {
        return $this->getConfig(self::XML_PATH_PWA_FEATUERE_PRODUCT_CONDITION);
    }

    /**
     * Receive magento config value
     *
     * @param string $path
     * @param string | int $store
     * @param ScopeInterface | null $scope
     * @return mixed
     */
    public function getConfig($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scope, $store);
    }

    /**
     * @param array $result
     * @param null $type
     * @return array
     */
    public function applyMetaConfig(array $result, $type = null)
    {
        $metaConfig = $this->getMetaConfig();

        if ($type == 'cms') {
            if (empty($result[PageInterface::META_DESCRIPTION]) && !empty($metaConfig['description'])) {
                $result[PageInterface::META_DESCRIPTION] = $metaConfig['description'];
            }
            if (empty($result[PageInterface::META_KEYWORDS]) && !empty($metaConfig['keywords'])) {
                $result[PageInterface::META_KEYWORDS] = $metaConfig['keywords'];
            }
            if (empty($result[PageInterface::META_TITLE]) && !empty($metaConfig['title'])) {
                $result[PageInterface::META_TITLE] = $metaConfig['title'];
            }
            if (!empty($result[PageInterface::META_TITLE])) {
                if (trim($metaConfig['prefix'])) {
                    $result[PageInterface::META_TITLE] = trim($metaConfig['prefix']) . ' ' . $result[PageInterface::META_TITLE];
                }
                if (trim($metaConfig['suffix'])) {
                    $result[PageInterface::META_TITLE] = $result[PageInterface::META_TITLE] . ' ' . trim($metaConfig['suffix']);
                }
            }
        }
        if ($type == 'cat') {
            if (empty($result[PageInterface::META_DESCRIPTION]) && !empty($metaConfig['description'])) {
                $result[PageInterface::META_DESCRIPTION] = $metaConfig['description'];
            }
            if (empty($result[PageInterface::META_KEYWORDS]) && !empty($metaConfig['keywords'])) {
                $result[PageInterface::META_KEYWORDS] = $metaConfig['keywords'];
            }
            if (!empty($result['name'])) {
                $metaTitle = $result['name'];
                if (trim($metaConfig['prefix'])) {
                    $metaTitle = trim($metaConfig['prefix']) . ' ' . $result['name'];
                }
                if (trim($metaConfig['suffix'])) {
                    $metaTitle .= ' ' . trim($metaConfig['suffix']);
                }
                $result[PageInterface::META_TITLE] = $metaTitle;
            }
        }
        if ($type == 'prod') {
            if (empty($result[PageInterface::META_DESCRIPTION])) {
                if (!empty($result['description'])) {
                    $result[PageInterface::META_DESCRIPTION] = $result['description'];
                } else {
                    if (!empty($metaConfig['description'])) {
                        $result[PageInterface::META_DESCRIPTION] = $metaConfig['description'];
                    }
                }
            }
            if (empty($result['meta_keyword']) && !empty($metaConfig['keywords'])) {
                $result['meta_keyword'] = $metaConfig['keywords'];
            }
            if (!empty($result['name'])) {
                $metaTitle = $result['name'];
                if (trim($metaConfig['prefix'])) {
                    $metaTitle = trim($metaConfig['prefix']) . ' ' . $result['name'];
                }
                if (trim($metaConfig['suffix'])) {
                    $metaTitle .= ' ' . trim($metaConfig['suffix']);
                }
                $result[PageInterface::META_TITLE] = $metaTitle;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getMetaConfig()
    {
        return [
            'description' => $this->getConfig(self::DEFAULT_GROUP . 'default_description'),
            'keywords' => $this->getConfig(self::DEFAULT_GROUP . 'default_keywords'),
            'title' => $this->getConfig(self::DEFAULT_GROUP . 'default_title'),
            'prefix' => $this->getConfig(self::DEFAULT_GROUP . 'title_prefix'),
            'suffix' => $this->getConfig(self::DEFAULT_GROUP . 'title_suffix'),
        ];
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getRootCategoryId()
    {
        return $this->_storeManager->getStore()->getRootCategoryId();
    }
}
