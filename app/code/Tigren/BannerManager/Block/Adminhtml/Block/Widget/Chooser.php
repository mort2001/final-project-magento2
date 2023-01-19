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

namespace Tigren\BannerManager\Block\Adminhtml\Block\Widget;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Tigren\BannerManager\Model\ResourceModel\Block;
use Tigren\BannerManager\Model\ResourceModel\Block\CollectionFactory;

/**
 * Class Chooser
 *
 * @package Tigren\BannerManager\Block\Adminhtml\Block\Widget
 */
class Chooser extends Extended
{
    /**
     * @var array
     */
    protected $_selectedBlocks = [];

    /**
     * @var Product
     */
    protected $_resourceBlock;

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
     * @param Block $resourceBlock
     * @param array $data
     */
    public function __construct(
        Context $context,
        Timezone $_stdTimezone,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        Block $resourceBlock,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_stdTimezone = $_stdTimezone;
        $this->_resourceBlock = $resourceBlock;
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
            ->createBlock('Tigren\BannerManager\Block\Adminhtml\Block\Widget\ChooserJs')
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
                $(grid.containerId).fire('blocks:changed', {element: element});
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
            'bannersmanager/block_widget/chooser',
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
        if ($column->getId() == 'in_blocks') {
            $selected = $this->getSelectedBlocks();
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('block_id', ['in' => $selected]);
            } else {
                $this->getCollection()->addFieldToFilter('block_id', ['nin' => $selected]);
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
    public function getSelectedBlocks()
    {
        if ($selectedBlocks = $this->getRequest()->getParam('selected_blocks', null)) {
            $this->setSelectedBlocks($selectedBlocks);
        }
        return $this->_selectedBlocks;
    }

    /**
     * Setter
     *
     * @param  $selectedBlocks
     * @return $this
     */
    public function setSelectedBlocks($selectedBlocks)
    {
        $this->_selectedBlocks = $selectedBlocks;
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
            ->addFieldToFilter('from_date', [['to' => $dateTimeNow], ['from_date', 'null' => '']])
            ->addFieldToFilter('to_date', [['from' => $dateTimeNow], ['to_date', 'null' => '']]);

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
            'in_blocks',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_blocks',
                'inline_css' => 'checkbox entities',
                'field_name' => 'in_blocks',
                'values' => $this->getSelectedBlocks(),
                'align' => 'center',
                'index' => 'block_id',
                'use_index' => true
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
                'column_css_class' => 'col-title',

            ]
        );


        return parent::_prepareColumns();
    }
}
