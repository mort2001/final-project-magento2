<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block\Adminhtml\Block\Edit\Tab;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Tigren\BannerManager\Model\Banner;
use Tigren\BannerManager\Model\BannerFactory;
use Tigren\BannerManager\Model\BlockFactory;

/**
 * Class Banners
 *
 * @package Tigren\BannerManager\Block\Adminhtml\Block\Edit\Tab
 */
class Banners extends Extended implements
    TabInterface
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var BannerFactory
     */
    protected $_bannerFactory;

    /**
     * @var BlockFactory
     */
    protected $_blockFactory;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param BannerFactory $bannerFactory
     * @param BlockFactory $blockFactory
     * @param Registry $coreRegistry
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        BannerFactory $bannerFactory,
        BlockFactory $blockFactory,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->_bannerFactory = $bannerFactory;
        $this->_blockFactory = $blockFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Banners');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Banners');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Rerieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->_getData(
            'grid_url'
        ) ? $this->_getData(
            'grid_url'
        ) : $this->getUrl(
            'bannersmanager/*/bannerGrid',
            ['_current' => true]
        );
    }

    /**
     * Set grid params
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('bannermanager_banner_grid');
        $this->setDefaultSort('banner_id');
        $this->setUseAjax(true);
        if ($this->getBlock() && $this->getBlock()->getId()) {
            $this->setDefaultFilter(['in_banners' => 1]);
        }
    }

    /**
     * Retirve currently edited banner model
     *
     * @return Banner
     */
    public function getBlock()
    {
        return $this->_coreRegistry->registry('bannermanager_block');
    }

    /**
     * Add filter
     *
     * @param object $column
     * @return $this
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in banner flag
        if ($column->getId() == 'in_banners') {
            $bannerIds = $this->_getSelectedBanners();
            if (empty($bannerIds)) {
                $bannerIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('banner_id', ['in' => $bannerIds]);
            } else {
                if ($bannerIds) {
                    $this->getCollection()->addFieldToFilter('banner_id', ['nin' => $bannerIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Retrieve selected banners
     *
     * @return array
     */
    protected function _getSelectedBanners()
    {
        $banners = array_keys($this->getSelectedBanners());
        return $banners;
    }

    /**
     * Retrieve banners
     *
     * @return array
     */
    public function getSelectedBanners()
    {
        $id = $this->getRequest()->getParam('block_id');
        if (!isset($id)) {
            $id = 0;
        }
        /** @var \Tigren\BannerManager\Model\Block $block */
        $block = $this->_blockFactory->create()->load($id);
        $banners = $block->getBannersPosition();
        if (!$banners) {
            return [];
        }

        $bannerIds = [];

        foreach ($banners as $banner) {
            $bannerId = $banner['banner_id'];
            $bannerIds[$bannerId] = ['id' => $bannerId, 'position' => $banner['position']];
        }

        return $bannerIds;
    }

    /**
     * Prepare collection
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->_bannerFactory->create()->getCollection();
        $id = $this->getRequest()->getParam('block_id');
        if ($id) {
            $collection->getBannerByBlock($id);
            $collection->addFilterToMap('banner_id', 'main_table.banner_id');
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Add columns to grid
     *
     * @return                                        $this
     * @throws                                        Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_banners',
            [
                'type' => 'checkbox',
                'name' => 'banner',
                'values' => $this->_getSelectedBanners(),
                'align' => 'center',
                'index' => 'banner_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'banner_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'banner_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'banner_title',
            [
                'header' => __('Title'),
                'index' => 'banner_title',
                'header_css_class' => 'col-title',
                'column_css_class' => 'col-title'
            ]
        );

        $this->addColumn(
            'description',
            [
                'header' => __('Description'),
                'index' => 'description',
                'width' => '215px',
                'header_css_class' => 'col-description',
                'column_css_class' => 'col-description'
            ]
        );

        $this->addColumn(
            'banner_image',
            [
                'header' => __('Desktop Image'),
                'index' => 'banner_image',
                'header_css_class' => 'col-image',
                'column_css_class' => 'col-image',
                'renderer' => '\Tigren\BannerManager\Block\Adminhtml\Banner\Widget\Renderer\Images',
            ]
        );

        $this->addColumn(
            'mobile_image',
            [
                'header' => __('Mobile Image'),
                'index' => 'mobile_image',
                'header_css_class' => 'col-image',
                'column_css_class' => 'col-image',
                'renderer' => '\Tigren\BannerManager\Block\Adminhtml\Banner\Widget\Renderer\Images',
            ]
        );

        $this->addColumn(
            'banner_url',
            [
                'header' => __('Banner Url'),
                'index' => 'banner_url',
                'header_css_class' => 'col-url',
                'column_css_class' => 'col-url'
            ]
        );

        $this->addColumn(
            'position',
            [
                'header' => __('Position'),
                'name' => 'position',
                'type' => 'number',
                'validate_class' => 'validate-number',
                'index' => 'position',
                'editable' => true,
                'edit_only' => true,
                'header_css_class' => 'col-position',
                'column_css_class' => 'col-position'
            ]
        );

        return parent::_prepareColumns();
    }
}
