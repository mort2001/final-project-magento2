<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

/**
 * Product Chooser for "Product Link" Cms Widget Plugin
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */

namespace Tigren\BannerManager\Block\Adminhtml\Banner\Widget;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Tigren\BannerManager\Model\ResourceModel\Banner;
use Tigren\BannerManager\Model\ResourceModel\Banner\CollectionFactory;

/**
 * Class Chooser
 *
 * @package Tigren\BannerManager\Block\Adminhtml\Banner\Widget
 */
class Chooser extends Extended
{
    /**
     * @var array
     */
    protected $_selectedBanners = [];

    /**
     * @var Product
     */
    protected $_resourceBanner;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * stdlib timezone.
     *
     * @var Timezone
     */
    protected $_stdTimezone;


    /**
     * Chooser constructor.
     *
     * @param Context $context
     * @param Timezone $_stdTimezone
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param Banner $resourceBanner
     * @param array $data
     */
    public function __construct(
        Context $context,
        Timezone $_stdTimezone,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        Banner $resourceBanner,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_stdTimezone = $_stdTimezone;
        $this->_resourceBanner = $resourceBanner;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param AbstractElement $element Form Element
     * @return AbstractElement
     * @throws LocalizedException
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $element->setData('after_element_html', $this->_getAfterElementHtml($element));
        return $element;
    }

    /**
     * @param  $element
     * @return string
     * @throws LocalizedException
     */
    public function _getAfterElementHtml($element)
    {
        $html = <<<HTML
    <style>
         .control .control-value {
            display: none !important;
        }
    </style>
HTML;

        $chooserHtml = $this->getLayout()
            ->createBlock('Tigren\BannerManager\Block\Adminhtml\Banner\Widget\ChooserJs')
            ->setElement($element);

        $html .= $chooserHtml->toHtml();

        return $html;
    }

    /**
     * @return string
     */
    public function getCheckboxCheckCallback()
    {
        return "function (grid, element) {
                $(grid.containerId).fire('banners:changed', {element: element});
            }";
    }

    /**
     * Adds additional parameter to URL for loading only products grid
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'bannersmanager/banners_widget/chooser',
            [
                '_current' => true,
                'uniq_id' => $this->getId(),

            ]
        );
    }

    /**
     * Block construction, prepare grid params
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setDefaultSort('name');
        $this->setUseAjax(true);
    }

    /**
     * @param Column $column
     * @return $this|Extended
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_banners') {
            $selected = $this->getSelectedBanners();
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('banner_id', ['in' => $selected]);
            } else {
                $this->getCollection()->addFieldToFilter('banner_id', ['nin' => $selected]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Getter
     *
     * @return array
     */
    public function getSelectedBanners()
    {
        if ($selectedBanners = $this->getRequest()->getParam('selected_banners', null)) {
            $this->setSelectedBanners($selectedBanners);
        }
        return $this->_selectedBanners;
    }

    /**
     * Setter
     *
     * @param  $selectedBanners
     * @return $this
     */
    public function setSelectedBanners($selectedBanners)
    {
        $this->_selectedBanners = $selectedBanners;
        return $this;
    }

    /**
     * Prepare products collection, defined collection filters (category, product type)
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        $dateTimeNow = $this->_stdTimezone->date()->format('Y-m-d H:i:s');
        $collection = $this->_collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('start_time', [['to' => $dateTimeNow], ['start_time', 'null' => '']])
            ->addFieldToFilter('end_time', [['from' => $dateTimeNow], ['end_time', 'null' => '']])
            ->setOrder('sort_order', 'ASC');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for products grid
     *
     * @return Extended
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_banners',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_banners',
                'inline_css' => 'checkbox entities',
                'field_name' => 'in_banners',
                'values' => $this->getSelectedBanners(),
                'align' => 'center',
                'index' => 'banner_id',
                'use_index' => true
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
                'column_css_class' => 'col-title',

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

        return parent::_prepareColumns();
    }
}
