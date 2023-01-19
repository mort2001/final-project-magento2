<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml\Event\Edit\Tab;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\LinkFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Directory\Model\Currency;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Tigren\Events\Model\Event;
use Tigren\Events\Model\EventFactory;

/**
 * Class Products
 *
 * @package Tigren\Events\Block\Adminhtml\Event\Edit\Tab
 */
class Products extends Extended
{
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var EventFactory
     */
    protected $_eventFactory;

    /**
     * @var LinkFactory
     */
    protected $_linkFactory;

    /**
     * @var Status
     */
    protected $_productStatus;

    /**
     * @var Visibility
     */
    protected $_productVisibility;

    /**
     * Products constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param Registry $coreRegistry
     * @param EventFactory $eventFactory
     * @param LinkFactory $linkFactory
     * @param Status $productStatus
     * @param Visibility $productVisibility
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Registry $coreRegistry,
        EventFactory $eventFactory,
        LinkFactory $linkFactory,
        Status $productStatus,
        Visibility $productVisibility,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_eventFactory = $eventFactory;
        $this->_linkFactory = $linkFactory;
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Rerieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->_getData('grid_url') ? $this->_getData('grid_url') : $this->getUrl(
            '*/*/productgrid',
            ['_current' => true]
        );
    }

    /**
     * Set grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('events_products_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        if ($this->getEvent() && $this->getEvent()->getId()) {
            $this->setDefaultFilter(['in_product' => 1]);
        }
    }

    /**
     * Retirve currently edited model
     *
     * @return Event
     */
    public function getEvent()
    {
        return $this->_coreRegistry->registry('events_event');
    }

    /**
     * Prepare collection
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->_linkFactory->create()->getProductCollection()
            ->addAttributeToSelect('*');
        $eventId = $this->getRequest()->getParam('event_id');
        $event = $this->_eventFactory->create()->load($eventId);
        if ($event->getId() && $event->getProductId()) {
            $productId = $event->getProductId();
            $collection->addFieldToFilter('entity_id', $productId);
        } else {
            $associatedProductIds = [];
            $events = $this->_eventFactory->create()->getCollection();
            foreach ($events as $event) {
                $associatedProductIds[] = $event->getProductId();
            }

            //Get id of products that have type 'event' and haven't associated with any event
            $eventProductIds = $this->getEventProductIds();
            foreach ($eventProductIds as $key => $id) {
                if (in_array($id, $associatedProductIds)) {
                    unset($eventProductIds[$key]);
                }
            }

            $collection->addFieldToFilter('type_id', 'event')
                ->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()])
                ->addFieldToFilter('entity_id', ['in' => $eventProductIds]);
            $collection->getSelect()->distinct(true)->join(
                ['stock_table' => $collection->getTable('cataloginventory_stock_status')],
                'e.entity_id = stock_table.product_id',
                []
            );
            $collection->getSelect()->where('stock_table.stock_status = 1');
        }
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return array
     */
    public function getEventProductIds()
    {
        $eventProductIds = [];
        $productCollection = $this->_linkFactory->create()->getProductCollection()
            ->addFieldToFilter('type_id', 'event')
            ->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
        foreach ($productCollection as $product) {
            $eventProductIds[] = $product->getId();
        }
        return $eventProductIds;
    }

    /**
     * Add columns to grid
     *
     * @return                                        Extended
     * @throws                                        Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_product',
            [
                'type' => 'radio',
                'html_name' => 'product_associated',
                'values' => $this->_getSelectedProduct(),
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'type' => 'number',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'product_thumbnail',
            [
                'header' => __('Thumbnail'),
                'align' => 'left',
                'width' => '97',
                'renderer' => 'Tigren\Events\Block\Adminhtml\Grid\Column\Renderer\Thumbnail'
            ]
        );

        $this->addColumn(
            'product_name',
            [
                'header' => __('Product Name'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );
        $this->addColumn(
            'product_sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );
        $this->addColumn(
            'product_quantity',
            [
                'header' => __('Quantity'),
                'index' => 'stock_qty',
                'header_css_class' => 'col-quantity',
                'column_css_class' => 'col-quantity'
            ]
        );
        $this->addColumn(
            'product_price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    Currency::XML_PATH_CURRENCY_BASE,
                    ScopeInterface::SCOPE_STORE
                ),
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve selected items key
     *
     * @return array
     */
    protected function _getSelectedProduct()
    {
        $eventId = $this->getRequest()->getParam('event_id', 0);

        $event = $this->_eventFactory->create()->load($eventId);
        $productIdArr = [$event->getProductId()];
        return $productIdArr;
    }
}
