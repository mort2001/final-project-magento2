<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Helper;

use DateTime;
use DateTimeZone;
use Exception;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Helper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Catalog data helper
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends AbstractHelper
{
    /**
     * Currently selected store ID if applicable
     *
     * @var int
     */
    protected $_storeId;

    /**
     *
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var Helper
     */
    protected $_resourceHelper;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var Uploader
     */
    protected $_uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $_fileSystem;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * category collection factory.
     *
     * @var CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @param Context $context
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param Registry $coreRegistry
     * @param CustomerSession $customerSession
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param CollectionFactory $categoryCollectionFactory
     * @param EncoderInterface $jsonEncoder
     * @param Helper $resourceHelper
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $fileSystem
     * @param TimezoneInterface $localeDate
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context                             $context,
        ResourceConnection                  $resource,
        StoreManagerInterface               $storeManager,
        Registry                            $coreRegistry,
        CustomerSession                     $customerSession,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        CollectionFactory                   $categoryCollectionFactory,
        EncoderInterface                    $jsonEncoder,
        Helper                              $resourceHelper,
        UploaderFactory                     $uploaderFactory,
        Filesystem                          $fileSystem,
        TimezoneInterface                   $localeDate
    )
    {
        $this->_resource = $resource;
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSession = $customerSession;
        $this->_backendUrl = $backendUrl;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_resourceHelper = $resourceHelper;
        $this->_uploaderFactory = $uploaderFactory;
        $this->_fileSystem = $fileSystem;
        $this->_localeDate = $localeDate;
        $this->_jsonEncoder = $jsonEncoder;

        parent::__construct($context);
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
     * get Banner Grid Url
     *
     * @return string
     */
    public function getBannerGridUrl()
    {
        return $this->_backendUrl->getUrl('bannermanager/block/bannergrid', ['_current' => true]);
    }

    /**
     * @return array
     */
    public function getDisplayTypeOptions()
    {
        return [
            ['label' => '-- Select Type --', 'value' => ''],
            ['label' => __('All Images'), 'value' => 1],
            ['label' => __('Random'), 'value' => 2],
            ['label' => __('Slider'), 'value' => 3],
            ['label' => __('Slider with Description'), 'value' => 4],
            ['label' => __('Basic Slider with Custom Direction Navigation'), 'value' => 5],
            ['label' => __('Slider with Min and Max Ranges'), 'value' => 6],
            ['label' => __('Basic Carousel'), 'value' => 7],
            ['label' => __('Fade'), 'value' => 8],
            ['label' => __('Fade with Description'), 'value' => 9],
            ['label' => __('Slider Only'), 'value' => 10]
        ];
    }

    /**
     * @return array
     */
    public function getPositionOptions()
    {
        return [
            [
                'label' => __('------- Please choose position -------'),
                'value' => '',
            ],
            [
                'label' => __('Popular positions'),
                'value' => [
                    ['value' => 'all-page-top', 'label' => __('All-Pages-Top')],
                    ['value' => 'cms-page-content-top', 'label' => __('Homepage-Content-Top')],
                    ['value' => 'homepage-main-banner', 'label' => __('Homepage-Main-Banner')],
                ],
            ],
            [
                'label' => __('Default for using in CMS page template'),
                'value' => [
                    ['value' => 'custom', 'label' => __('Custom')],
                ],
            ],
            [
                'label' => __('General (will be disaplyed on all pages)'),
                'value' => [
                    ['value' => 'sidebar-main-top', 'label' => __('Sidebar-Main-Top')],
                    ['value' => 'sidebar-main-bottom', 'label' => __('Sidebar-Main-Bottom')],
                    ['value' => 'sidebar-additional-top', 'label' => __('Sidebar-Additional-Top')],
                    ['value' => 'sidebar-additional-bottom', 'label' => __('Sidebar-Additional-Bottom')],
                    ['value' => 'content-top', 'label' => __('Content-Top')],
                    ['value' => 'menu-top', 'label' => __('Menu-Top')],
                    ['value' => 'menu-bottom', 'label' => __('Menu-Bottom')],
                    ['value' => 'page-bottom', 'label' => __('Page-Bottom')],
                ],
            ],
            [
                'label' => __('Catalog and product'),
                'value' => [
                    ['value' => 'catalog-sidebar-main-top', 'label' => __('Catalog-Sidebar-Main-Top')],
                    ['value' => 'catalog-sidebar-main-bottom', 'label' => __('Catalog-Sidebar-Main-Bottom')],
                    ['value' => 'catalog-sidebar-additional-top', 'label' => __('Catalog-Sidebar-Additional-Top')],
                    [
                        'value' => 'catalog-sidebar-additional-bottom',
                        'label' => __('Catalog-Sidebar-Additional-Bottom')
                    ],
                    ['value' => 'catalog-content-top', 'label' => __('Catalog-Content-Top')],
                    ['value' => 'catalog-menu-top', 'label' => __('Catalog-Menu-Top')],
                    ['value' => 'catalog-menu-bottom', 'label' => __('Catalog-Menu-Bottom')],
                    ['value' => 'catalog-page-bottom', 'label' => __('Catalog-Page-Bottom')],
                ],
            ],
            [
                'label' => __('Category only'),
                'value' => [
                    ['value' => 'category-sidebar-main-top', 'label' => __('Category-Sidebar-Main-Top')],
                    ['value' => 'category-sidebar-main-bottom', 'label' => __('Category-Sidebar-Main-Bottom')],
                    ['value' => 'category-sidebar-additional-top', 'label' => __('Category-Sidebar-Additional-Top')],
                    [
                        'value' => 'category-sidebar-additional-bottom',
                        'label' => __('Category-Sidebar-Additional-Bottom')
                    ],
                    ['value' => 'category-content-top', 'label' => __('Category-Content-Top')],
                    ['value' => 'category-menu-top', 'label' => __('Category-Menu-Top')],
                    ['value' => 'category-menu-bottom', 'label' => __('Category-Menu-Bottom')],
                    ['value' => 'category-page-bottom', 'label' => __('Category-Page-Bottom')],
                ],
            ],
            [
                'label' => __('Product only'),
                'value' => [
                    ['value' => 'product-sidebar-main-top', 'label' => __('Product-Sidebar-Main-Top')],
                    ['value' => 'product-sidebar-main-bottom', 'label' => __('Product-Sidebar-Main-Bottom')],
                    ['value' => 'product-sidebar-additional-top', 'label' => __('Product-Sidebar-Additional-Top')],
                    [
                        'value' => 'product-sidebar-additional-bottom',
                        'label' => __('Product-Sidebar-Additional-Bottom')
                    ],
                    ['value' => 'product-content-top', 'label' => __('Product-Content-Top')],
                    ['value' => 'product-menu-top', 'label' => __('Product-Menu-Top')],
                    ['value' => 'product-menu-bottom', 'label' => __('Product-Menu-Bottom')],
                    ['value' => 'product-page-bottom', 'label' => __('Product-Page-Bottom')],
                ],
            ],
            [
                'label' => __('Customer'),
                'value' => [
                    ['value' => 'customer-content-top', 'label' => __('Customer-Content-Top')],
                    ['value' => 'customer-sidebar-main-top', 'label' => __('Customer-Siderbar-Main-Top')],
                    ['value' => 'customer-sidebar-main-bottom', 'label' => __('Customer-Siderbar-Main-Bottom')],
                    ['value' => 'customer-sidebar-additional-top', 'label' => __('Customer-Siderbar-Additional-Top')],
                    [
                        'value' => 'customer-sidebar-additional-bottom',
                        'label' => __('Customer-Siderbar-Additional-Bottom')
                    ],
                ],
            ],
            [
                'label' => __('Cart & Checkout'),
                'value' => [
                    ['value' => 'cart-content-top', 'label' => __('Cart-Content-Top')],
                    ['value' => 'checkout-content-top', 'label' => __('Checkout-Content-Top')],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getTargetOptions()
    {
        return [
            ['value' => 0, 'label' => __('Open in the same window')],
            ['value' => 1, 'label' => __('Open in new window')]
        ];
    }

    /**
     * get category options.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCategoryOptions()
    {
        $categoriesArray = $this->_categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('path', 'asc')
            ->load()
            ->toArray();

        $categories = [];
        foreach ($categoriesArray as $categoryId => $category) {
            if (isset($category['name']) && isset($category['level'])) {
                $categories[] = [
                    'label' => $category['name'],
                    'level' => $category['level'],
                    'value' => $categoryId,
                ];
            }
        }

        return $categories;
    }

    /*
     * @return string
     */
    /**
     * @param  $image
     * @return string
     * @throws NoSuchEntityException
     */
    public function getImageUrl($image)
    {
        if (!$image) {
            return null;
        }
        $path = $this->_fileSystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            'tigren/banners/'
        );

        if (file_exists($path . $image)) {
            $path = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $path . 'tigren/banners/' . $image;
        } else {
            return '';
        }
    }

    /**
     * @param  $dateTime
     * @return string
     * @throws Exception
     */
    public function convertDateTime($dateTime)
    {
        return date('Y-m-d H:i:s', strtotime($dateTime));
    }
}
