<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\BannerManager\Helper\Data;
use Tigren\BannerManager\Model\Block as BlockModel;
use Tigren\BannerManager\Model\BlockFactory;
use Tigren\BannerManager\Model\ResourceModel\Banner\Collection;
use Tigren\BannerManager\Model\ResourceModel\Banner\CollectionFactory;

/**
 * Block item.
 *
 * @category Magestore
 * @package  Magestore_Bannerslider
 * @module   Bannerslider
 * @author   Magestore Developer
 */
class BannerCustom extends Template
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
    const DISPLAYTYPE_SLIDER_TEMPLATE = 'type/slider.phtml';

    /**
     * template for slider with description banner.
     */
    const DISPLAYTYPE_SLIDER_WITH_DESCRIPTION_TEMPLATE = 'type/slider_with_description.phtml';

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
     * block factory.
     *
     * @var BlockFactory
     */
    protected $_blockFactory;

    /**
     * block model.
     *
     * @var BlockModel
     */
    protected $_block;

    /**
     * block id.
     *
     * @var int
     */
    protected $_blockId;

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
     * [__construct description].
     *
     * @param Context $context
     * @param CollectionFactory $bannerCollectionFactory
     * @param BlockFactory $blockFactory
     * @param BlockModel $block
     * @param DateTime $stdlibDateTime
     * @param Data $bannerHelper
     * @param StoreManagerInterface $storeManager
     * @param Timezone $_stdTimezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $bannerCollectionFactory,
        BlockFactory $blockFactory,
        BlockModel $block,
        DateTime $stdlibDateTime,
        Data $bannerHelper,
        Timezone $_stdTimezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_blockFactory = $blockFactory;
        $this->_block = $block;
        $this->_stdlibDateTime = $stdlibDateTime;
        $this->_bannerHelper = $bannerHelper;
        $this->_storeManager = $context->getStoreManager();
        $this->_bannerCollectionFactory = $bannerCollectionFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_stdTimezone = $_stdTimezone;
    }

    /**
     * @return bool
     */
    public function isShowTitle()
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getAdditionalClass()
    {
        return $this->getData('additional_class');
    }

    /**
     * get first banner.
     *
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
        $dateTimeNow = $this->_stdTimezone->date()->format('Y-m-d H:i:s');

        $bannerCollection = $this->_bannerCollectionFactory->create()
            ->getBannerByBlock($this->_block->getId())
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('start_time', [['to' => $dateTimeNow], ['start_time', 'null' => '']])
            ->addFieldToFilter('end_time', [['gteq' => $dateTimeNow], ['end_time', 'null' => '']])
            ->setOrder('sort_order', 'ASC');

        if ($this->_block->getDisplayType() == 2) {
            $bannerCollection->getSelect()->order('rand()');
            $bannerCollection->setPageSize(1);
        }

        return $bannerCollection;
    }

    /**
     * get banner image url.
     *
     * @param \Tigren\BannerManager\Model\Banner $banner
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBannerImageUrl(\Tigren\BannerManager\Model\Banner $banner)
    {
        return $this->_bannerHelper->getImageUrl($banner->getBannerImage());
    }

    /**
     * get banner image url.
     *
     * @param \Tigren\BannerManager\Model\Banner $banner
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMobileImageUrl(\Tigren\BannerManager\Model\Banner $banner)
    {
        return $this->_bannerHelper->getImageUrl($banner->getMobileImage());
    }

    /**
     * get block max width.
     *
     * @return integer
     */
    public function getBlockMaxWidth()
    {
        return $this->_block->getBlockMaxWidth();
    }

    /**
     * get flexslider html id.
     *
     * @return string
     */
    public function getBannerItemHtmlId()
    {
        return 'tigren-bannermanager-banner-' . $this->getBlock()->getId() . $this->_stdlibDateTime->gmtTimestamp();
    }

    /**
     * @return BlockModel
     */
    public function getBlock()
    {
        return $this->_block;
    }

    /**
     * set slider model.
     *
     * @param BlockModel $block [description]
     * @return BannerCustom
     */
    public function setBlock(BlockModel $block)
    {
        $this->_block = $block;

        return $this;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $blockId = $this->getBlockId();
        if ($blockId) {
            $this->setBlockId($blockId);
        } else {
            return '';
        }

        if (!$this->_block->getId() || $this->_block->getIsActive() === 1 || !$this->getBannerCollection()->getSize()) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Set slider ID and set template.
     *
     * @param int $blockId
     * @return BannerCustom
     */
    public function setBlockId($blockId)
    {
        $this->_blockId = $blockId;

        $block = $this->_blockFactory->create()->load($this->_blockId);
        if ($block->getId()) {
            $this->setBlock($block);

            switch ($block->getDisplayType()) {
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
                    $typeTemplate = self::DISPLAYTYPE_FADE_TEMPLATE;
                    break;
                case '6':
                    $typeTemplate = self::DISPLAYTYPE_FADE_WITH_DESCRIPTION_TEMPLATE;
                    break;
                default:
                    $typeTemplate = self::DISPLAYTYPE_SLIDER_TEMPLATE;
                    break;
            }

            $this->setTemplate($typeTemplate);
        }

        return $this;
    }
}
