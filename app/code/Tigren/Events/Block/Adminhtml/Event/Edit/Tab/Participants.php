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
use Magento\Framework\Registry;
use Tigren\Events\Model\Catalog\ProductFactory;
use Tigren\Events\Model\EventFactory;
use Tigren\Events\Model\ParticipantFactory;
use Tigren\Events\Model\ParticipantStatus;

/**
 * Class Participants
 *
 * @package Tigren\Events\Block\Adminhtml\Event\Edit\Tab
 */
class Participants extends Extended
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
     * @var ProductFactory
     */
    protected $_eventsProductFactory;

    /**
     * @var ParticipantFactory
     */
    protected $_participantFactory;

    /**
     * @var ParticipantStatus
     */
    protected $_participantStatus;

    /**
     * Participants constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param EventFactory $eventFactory
     * @param ProductFactory $eventsProductFactory
     * @param ParticipantFactory $participantFactory
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        EventFactory $eventFactory,
        ProductFactory $eventsProductFactory,
        ParticipantFactory $participantFactory,
        Registry $coreRegistry,
        ParticipantStatus $participantStatus,
        array $data = []
    ) {
        $this->_eventFactory = $eventFactory;
        $this->_eventsProductFactory = $eventsProductFactory;
        $this->_participantFactory = $participantFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_participantStatus = $participantStatus;
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
            '*/*/participantgrid',
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
        $this->setId('events_participant_grid');
        $this->setDefaultSort($this->getEvent()->getPrice() > 0 ? 'entity_id' : 'participant_id');
        $this->setUseAjax(true);
    }

    /**
     * @return mixed
     */
    protected function getEvent()
    {
        $eventId = $this->getRequest()->getParam('event_id');
        return $this->_eventFactory->create()->load($eventId);
    }

    /**
     * Prepare collection
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        $event = $this->getEvent();
        $eventPrice = $event->getPrice();
        $collection = null;
        if ($eventPrice > 0) {
            $productId = (int)$event->getProductId();
            if ($productId && $productId > 0) {
                $product = $this->_eventsProductFactory->create()->load($productId);
                if ($product->getId()) {
                    $collection = $product->getOrdererAddressCollection();
                }
            }
        } else {
            $eventId = $this->getRequest()->getParam('event_id');
            $collection = $this->_participantFactory->create()->getCollection()
                ->addFieldToFilter('event_id', $eventId);
            //                ->addFieldToFilter('status', 1)
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return array
     */
    protected function _getSelectedParticipant()
    {
        $categories = array_keys($this->getSelectedParticipant());
        return $categories;
    }

    /**
     * @return array
     */
    public function getSelectedParticipant()
    {
        $eventId = $this->getRequest()->getParam('event_id');
        $event = $this->_eventFactory->create()->load($eventId);
        $participant = $event->getParticipant();

        if (!$participant) {
            return [];
        }

        $participantIds = [];

        foreach ($participant as $participantId) {
            $categoryIds[$participantId] = ['id' => $participantId];
        }

        return $participantIds;
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
        //        $this->addColumn(
        //            'in_participant',
        //            [
        //                'type' => 'checkbox',
        //                'name' => 'in_participant',
        //                'values' => $this->_getSelectedParticipant(),
        //                'align' => 'center',
        //                'index' => 'participant_id',
        //                'header_css_class' => 'col-select',
        //                'column_css_class' => 'col-select'
        //            ]
        //        );

        $price = $this->getEvent()->getPrice();
        $this->addColumn(
            'participant_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'type' => 'number',
                'index' => $price > 0 ? 'entity_id' : 'participant_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $fullnameRenderer = $price > 0 ? 'Tigren\Events\Block\Adminhtml\Grid\Column\Renderer\Fullname' : '';
        $this->addColumn(
            'participant_fullname',
            [
                'header' => __('Full Name'),
                'index' => $price > 0 ? 'fullname' : 'fullname',
                'header_css_class' => 'col-fullname',
                'column_css_class' => 'col-fullname',
                'renderer' => $fullnameRenderer
            ]
        );
        $this->addColumn(
            'participant_phone',
            [
                'header' => __('Phone'),
                'index' => $price > 0 ? 'telephone' : 'phone',
                'header_css_class' => 'col-phone',
                'column_css_class' => 'col-phone'
            ]
        );
        $this->addColumn(
            'participant_email',
            [
                'header' => __('Email'),
                'index' => $price > 0 ? 'email' : 'email',
                'header_css_class' => 'col-email',
                'column_css_class' => 'col-email'
            ]
        );
        $locationRenderer = $price > 0 ? 'Tigren\Events\Block\Adminhtml\Grid\Column\Renderer\Location' : '';
        $this->addColumn(
            'participant_address',
            [
                'header' => __('Address'),
                'index' => $price > 0 ? 'location' : 'address',
                'header_css_class' => 'col-address',
                'column_css_class' => 'col-address',
                'renderer' => $locationRenderer
            ]
        );
        $this->addColumn(
            'participant_status',
            [
                'header' => __('Arrival'),
                'index' => $price > 0 ? 'status' : 'status',
                'header_css_class' => 'col-address',
                'column_css_class' => 'col-address',
                'type' => 'options',
                'options' => $this->_participantStatus->getOptionArray()
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return $this|Extended
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('participant_id');
        $this->getMassactionBlock()->setTemplate('Tigren_Events::events/grid/massaction_extended.phtml');
        $this->getMassactionBlock()->setFormFieldName('participant');

        $statuses = $this->_participantStatus->getOptionArray();

        $this->getMassactionBlock()->addItem(
            'status',
            [
                'label' => __('Change Status'),
                'url' => $this->getUrl('*/*/massStatus', ['_current' => true]),
                'additional' => [
                    'visibility' => [
                        'name' => 'status',
                        'type' => 'select',
                        'label' => __('Status'),
                        'values' => $statuses
                    ]
                ]
            ]
        );
        return $this;
    }
}
