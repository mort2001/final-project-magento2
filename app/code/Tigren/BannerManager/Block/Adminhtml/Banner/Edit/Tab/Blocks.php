<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block\Adminhtml\Banner\Edit\Tab;

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
 * Class Blocks
 *
 * @package Tigren\BannerManager\Block\Adminhtml\Banner\Edit\Tab
 */
class Blocks extends Extended implements
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
        return __('Blocks');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Blocks');
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
            'bannersmanager/*/blockGrid',
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
        $this->setId('bannermanager_block_grid');
        $this->setDefaultSort('block_id');
        $this->setUseAjax(true);
        if ($this->getBanner() && $this->getBanner()->getId()) {
            $this->setDefaultFilter(['in_blocks' => 1]);
        }
    }

    /**
     * Retirve currently edited banner model
     *
     * @return Banner
     */
    public function getBanner()
    {
        return $this->_coreRegistry->registry('bannermanager_banner');
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
        // Set custom filter for in block flag
        if ($column->getId() == 'in_blocks') {
            $blockIds = $this->_getSelectedBlocks();
            if (empty($blockIds)) {
                $blockIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('block_id', ['in' => $blockIds]);
            } else {
                if ($blockIds) {
                    $this->getCollection()->addFieldToFilter('block_id', ['nin' => $blockIds]);
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
    protected function _getSelectedBlocks()
    {
        $blocks = array_keys($this->getSelectedBlocks());
        return $blocks;
    }

    /**
     * Retrieve blocks
     *
     * @return array
     */
    public function getSelectedBlocks()
    {
        $id = $this->getRequest()->getParam('image_id');
        if (!isset($id)) {
            $id = 0;
        }

        $banner = $this->_bannerFactory->create()->load($id);
        $blocks = $banner->getBlocks();

        if (!$blocks) {
            return [];
        }

        $blockIds = [];

        foreach ($blocks as $blockId) {
            $blockIds[$blockId] = ['id' => $blockId];
        }

        return $blockIds;
    }

    /**
     * Prepare collection
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->_blockFactory->create()->getCollection();
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
            'in_blocks',
            [
                'type' => 'checkbox',
                'name' => 'banner',
                'values' => $this->_getSelectedBlocks(),
                'align' => 'center',
                'index' => 'block_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'block_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'block_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'block_title',
            [
                'header' => __('Title'),
                'index' => 'block_title',
                'header_css_class' => 'col-title',
                'column_css_class' => 'col-title'
            ]
        );

        $this->addColumn(
            'block_position',
            [
                'header' => __('Block Position'),
                'index' => 'block_position',
                'header_css_class' => 'col-position',
                'column_css_class' => 'col-position'
            ]
        );

        $this->addColumn(
            'from_date',
            [
                'header' => __('From'),
                'type' => 'date',
                'index' => 'from_date',
                'header_css_class' => 'col-from-date',
                'column_css_class' => 'col-from-date'
            ]
        );

        $this->addColumn(
            'to_date',
            [
                'header' => __('To'),
                'type' => 'date',
                'index' => 'to_date',
                'header_css_class' => 'col-to-date',
                'column_css_class' => 'col-to-date'
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
