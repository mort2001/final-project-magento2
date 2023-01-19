<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block\Widget;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Block\BlockInterface;
use Tigren\BannerManager\Helper\Data;
use Tigren\BannerManager\Model\Banner;
use Tigren\BannerManager\Model\ResourceModel\Banner\Collection;
use Tigren\BannerManager\Model\ResourceModel\Banner\CollectionFactory;

/**
 * Class Instance
 *
 * @package Tigren\BannerManager\Block\Widget
 */
class Instance extends Template implements BlockInterface
{
    /**
     * template for all image banner.
     */
    const DISPLAYTYPE_ALL_IMAGE_TEMPLATE = 'type/all_image.phtml';

    /**
     * template for random banner.
     */
    const DISPLAYTYPE_RANDOM_TEMPLATE = 'type/random.phtml';

    /**
     * template for slider banner.
     */
    const DISPLAYTYPE_SLIDER_TEMPLATE = 'type/slide/slider.phtml';

    /**
     * template for slider with description banner.
     */
    const DISPLAYTYPE_SLIDER_WITH_DESCRIPTION_TEMPLATE = 'type/slide/slider_with_description.phtml';

    /**
     * template for Basic Slider customDirectionNav.
     */
    const BASIC_SLIDE_CUSTOM_DIRECTION_NAV_TEMPLATE = 'type/slide/custom_direction_nav.phtml';

    /**
     * template for Basic Slider customDirectionNav.
     */
    const MIN_MAX_RANGES_TEMPLATE = 'type/slide/min_max_ranges.phtml';

    /**
     * template for Basic Carousel.
     */
    const BASIC_CAROUSEL_TEMPLATE = 'type/slide/carousel.phtml';

    /**
     * template for fade banner.
     */
    const DISPLAYTYPE_FADE_TEMPLATE = 'type/fade.phtml';

    /**
     * template for fade with description banner.
     */
    const DISPLAYTYPE_FADE_WITH_DESCRIPTION_TEMPLATE = 'type/fade_with_description.phtml';

    /**
     * Date conversion model.
     *
     * @var DateTime
     */
    protected $_stdlibDateTime;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * banner helper.
     *
     * @var Data
     */
    protected $_bannerHelper;

    /**
     * @var CollectionFactory
     */
    protected $_bannerCollectionFactory;

    /**
     * scope config.
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * stdlib timezone.
     *
     * @var Timezone
     */
    protected $_stdTimezone;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * Instance constructor.
     *
     * @param Context $context
     * @param CollectionFactory $bannerCollectionFactory
     * @param DateTime $stdlibDateTime
     * @param Session $customerSession
     * @param Data $bannerHelper
     * @param Timezone $_stdTimezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $bannerCollectionFactory,
        DateTime $stdlibDateTime,
        Session $customerSession,
        Data $bannerHelper,
        Timezone $_stdTimezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_stdlibDateTime = $stdlibDateTime;
        $this->_bannerHelper = $bannerHelper;
        $this->_storeManager = $context->getStoreManager();
        $this->_customerSession = $customerSession;
        $this->_bannerCollectionFactory = $bannerCollectionFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_stdTimezone = $_stdTimezone;
    }

    /**
     * @return string
     */
    public function getBannerHtmlId()
    {
        $htmlId = 'mb-banner-' . $this->getData('unique_id');
        return $htmlId;
    }

    /**
     * @return bool
     */
    public function isShowTitle()
    {
        return false;
    }

    /**
     * @return DataObject
     */
    public function getFirstBannerItem()
    {
        return $this->getBannerCollection()
            ->setPageSize(1)
            ->setCurPage(1)
            ->getFirstItem();
    }

    /**
     * get banner collection of slider.
     *
     * @return Collection
     */
    public function getBannerCollection()
    {
        $bannerIds = $this->getData('banner_id');
        $bannerIdsArr = explode(',', $bannerIds);
        $dateTimeNow = $this->_stdTimezone->date(null, null, false)
            ->format('Y-m-d H:i:s');

        $bannerCollection = $this->_bannerCollectionFactory->create()
            ->addFieldToFilter('banner_id', ['in' => $bannerIdsArr])
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('start_time', [['to' => $dateTimeNow], ['start_time', 'null' => '']])
            ->addFieldToFilter('end_time', [['gteq' => $dateTimeNow], ['end_time', 'null' => '']])
            ->setOrder('sort_order', 'ASC');

        if ($this->getData('display_type') == 2) {
            $bannerCollection->getSelect()->order('rand()');
            $bannerCollection->setPageSize(1);
        }

        return $bannerCollection;
    }

    /**
     * get banner image url.
     *
     * @param Banner $banner
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBannerImageUrl(Banner $banner)
    {
        return $this->_bannerHelper->getImageUrl($banner->getBannerImage());
    }

    /**
     * get banner image url.
     *
     * @param Banner $banner
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMobileImageUrl(Banner $banner)
    {
        return $this->_bannerHelper->getImageUrl($banner->getMobileImage());
    }

    /**
     * get flexslider html id.
     *
     * @return string
     */
    public function getBannerItemHtmlId()
    {
        return 'tigren-bannermanager-banner-' . $this->getData('unique_id') . $this->_stdlibDateTime->gmtTimestamp();
    }

    /**
     * @return mixed
     */
    public function getAdditionalClass()
    {
        return $this->getData('additional_class');
    }

    /**
     * @return mixed
     */
    public function getMinImages()
    {
        return $this->getData('min_items');
    }

    /**
     * @return mixed
     */
    public function getMaxImages()
    {
        return $this->getData('max_items');
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        if ($this->getData('is_active') && $this->_isValidCustomer()) {
            if (empty($this->_template)) {
                $this->_template = $this->getCustomTemplate();
            } else {
                $this->_template = $this->getTypeTemplate();
            }

            $this->setTemplate($this->_template);

            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @return boolean
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _isValidCustomer()
    {
        $customerGroups = explode(',', $this->getData('customer_group'));

        if (in_array((int)$this->_customerSession->getCustomerGroupId(), $customerGroups)) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTypeTemplate()
    {
        $typeTemplate = self::DISPLAYTYPE_SLIDER_TEMPLATE;

        $displayType = $this->getData('display_type');
        if ($displayType) {
            switch ($displayType) {
                case '1':
                    $typeTemplate = self::DISPLAYTYPE_ALL_IMAGE_TEMPLATE;
                    break;
                case '2':
                    $typeTemplate = self::DISPLAYTYPE_RANDOM_TEMPLATE;
                    break;
                case '3':
                    $typeTemplate = self::DISPLAYTYPE_SLIDER_TEMPLATE;
                    break;
                case '4':
                    $typeTemplate = self::DISPLAYTYPE_SLIDER_WITH_DESCRIPTION_TEMPLATE;
                    break;
                case '5':
                    $typeTemplate = self::BASIC_SLIDE_CUSTOM_DIRECTION_NAV_TEMPLATE;
                    break;
                case '6':
                    $typeTemplate = self::MIN_MAX_RANGES_TEMPLATE;
                    break;
                case '7':
                    $typeTemplate = self::BASIC_CAROUSEL_TEMPLATE;
                    break;
                case '8':
                    $typeTemplate = self::DISPLAYTYPE_FADE_TEMPLATE;
                    break;
                case '9':
                    $typeTemplate = self::DISPLAYTYPE_FADE_WITH_DESCRIPTION_TEMPLATE;
                    break;
                default:
                    $typeTemplate = self::DISPLAYTYPE_SLIDER_TEMPLATE;
                    break;
            }
        }

        return $typeTemplate;
    }
}
