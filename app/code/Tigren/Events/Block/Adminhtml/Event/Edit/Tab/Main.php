<?php

/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml\Event\Edit\Tab;

use IntlDateFormatter;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Tigren\Events\Helper\Data;

/**
 * Class Main
 *
 * @package Tigren\Events\Block\Adminhtml\Event\Edit\Tab
 */
class Main extends Generic
{
    /**
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var Data
     */
    protected $_eventsHelper;

    /**
     * @var string
     */
    protected $defaultColor = '3366CC';

    /**
     * @var Config
     */
    protected $wysiwygConfig;

    /**
     * Main constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param Data $eventsHelper
     * @param Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        Data $eventsHelper,
        Config $wysiwygConfig,
        array $data = []
    ) {
        $this->wysiwygConfig = $wysiwygConfig;
        $this->_systemStore = $systemStore;
        $this->_eventsHelper = $eventsHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return Generic
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _prepareForm()
    {
        $event = $this->_coreRegistry->registry('events_event');
        $eventId = $event->getId();
        $typeTimeConfig = $this->_eventsHelper->getTypeTimeValue();
        $typeTimeShow = $typeTimeConfig == 1 ? 'HH:mm:ss' : 'hh:mm:ss a';

        /**
         *
         *
         * @var Form $form
         */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('event_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($eventId) {
            $fieldset->addField('event_id', 'hidden', ['name' => 'event_id']);
        }

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Event Name'),
                'title' => __('Event Name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'location',
            'text',
            [
                'name' => 'location',
                'label' => __('Location'),
                'title' => __('Location')
            ]
        );

        if ($eventId && $event->getProductId()) {
            $event->setData('prd_price', $event->getPrice());
            $fieldset->addField(
                'prd_price',
                'text',
                [
                    'label' => __('Price'),
                    'title' => __('Price'),
                    'readonly' => 'readonly',
                    'style' => 'border-width: 1px !important'
                ]
            );
        }
        if ($eventId && $event->getProductId()) {
            $event->setData('no_of_participant', $event->getNoOfParticipant());
            $fieldset->addField(
                'no_of_participant',
                'text',
                [
                    'label' => __('Number of Participant'),
                    'title' => __('Number of Participant'),
                    'readonly' => 'readonly'
                ]
            );
        } else {
            $fieldset->addField(
                'number_of_participant',
                'text',
                [
                    'name' => 'number_of_participant',
                    'label' => __('Number of Participant'),
                    'title' => __('Number of Participant'),
                    'required' => true,
                    'class' => __('validate-zero-or-greater')
                ]
            );
        }

        $avatarDisplay = '';
        if ($event->getAvatar()) {
            $avatarDisplay .= $this->getImageHtml('avatar', $event->getAvatar(), 'tigren/events/event/avatar/');
            $avatarDisplay .= $this->getDeleteCheckboxHtml();
        }
        $fieldset->addField(
            'avatar',
            'file',
            [
                'name' => 'avatar',
                'label' => __('Event Avatar'),
                'title' => __('Event Avatar'),
                'after_element_html' => $avatarDisplay
            ]
        );

        $dateFormat = $this->_localeDate->getDateFormat(IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(IntlDateFormatter::SHORT);
        $style = 'color: #000;background-color: #fff; font-weight: bold; font-size: 13px; width: auto;';

        $fieldset->addField(
            'require_time',
            'select',
            [
                'name' => 'require_time',
                'label' => __('Require Time'),
                'title' => __('Require Time'),
                'required' => true,
                "values" => [
                    ["value" => 0, "label" => __("No")],
                    ["value" => 1, "label" => __("Yes")],
                ],
                'note' => "If 'No' the time will be set to begin of day (00:00:00 or 12:00:00 AM - base on config show time) and will not display at the frontend"
            ]
        );

        if (!$event->getId()) {
            $event->setData('require_time', 1);
        }

        $fieldset->addField(
            'start_time',
            'date',
            [
                'name' => 'start_time',
                'label' => __('Start Time'),
                'title' => __('Start Time'),
                'style' => $style,
                'required' => true,
                'class' => __('validate-date'),
                'date_format' => 'MM/dd/Y',
                'time_format' => $typeTimeShow,
                'note' => 'MM/dd/Y ' . $typeTimeShow
            ]
        );

        $fieldset->addField(
            'end_time',
            'date',
            [
                'name' => 'end_time',
                'label' => __('End Time'),
                'title' => __('End Time'),
                'style' => $style,
                'required' => true,
                'class' => __('validate-date'),
                'date_format' => 'MM/dd/Y',
                'time_format' => $typeTimeShow,
                'note' => 'MM/dd/Y ' . $typeTimeShow
            ]
        );

        $fieldset->addField(
            'registration_deadline',
            'date',
            [
                'name' => 'registration_deadline',
                'label' => __('Registration Deadline'),
                'title' => __('Registration Deadline'),
                'style' => $style,
                'class' => __('validate-date'),
                'date_format' => 'MM/dd/Y',
                'time_format' => $typeTimeShow,
                'note' => 'MM/dd/Y ' . $typeTimeShow
            ]
        );

        $fieldset->addField(
            'description',
            'editor',
            [
                'rows' => '5',
                'cols' => '30',
                'wysiwyg' => true,
                'config' => $this->wysiwygConfig->getConfig(),
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('Description')
            ]
        );

        if (!$this->_storeManager->hasSingleStore()) { //Check if store has only one store view
            $field = $fieldset->addField(
                'select_stores',
                'multiselect',
                [
                    'label' => __('Store View'),
                    'required' => true,
                    'name' => 'stores[]',
                    'values' => $this->_systemStore->getStoreValuesForForm(false, true)
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
            $event->setSelectStores($event->getStores());
        } else {
            $fieldset->addField(
                'select_stores',
                'hidden',
                [
                    'name' => 'stores[]',
                    'value' => $this->_storeManager->getStore(true)->getId()
                ]
            );
            $event->setSelectStores($this->_storeManager->getStore(true)->getId());
        }

        $fieldset->addField(
            'allow_register',
            'select',
            [
                'name' => 'allow_register',
                'label' => __('Allow Registration for Event'),
                'title' => __('Allow Registration for Event'),
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')]
            ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')]
            ]
        );

        $fieldset->addField(
            'color',
            'text',
            [
                'name' => 'color',
                'label' => __('Color'),
                'title' => __('Color'),
                'class' => __('color')
            ]
        );

        if (!$event->getId()) {                         //Add new
            $event->setData('status', '1');
            $event->setData('allow_register', '1');
        }
        if (empty($event->getData('color'))) {
            $event->setData('color', $this->defaultColor);
        }

        $form->setValues($event->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param  $field
     * @param  $imageName
     * @param  $dir
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getImageHtml($field, $imageName, $dir)
    {
        $html = '';
        if ($imageName) {
            $html .= '<p style="margin-top: 5px">';
            $html .= '<image style="min-width:100px;max-width:50%;" src="' . $this->_eventsHelper->getImageUrl(
                    $imageName,
                    $dir
                ) . '" />';
            $html .= '<input type="hidden" value="' . $imageName . '" name="old_' . $field . '"/>';
            $html .= '</p>';
        }
        return $html;
    }

    /**
     * @return string
     */
    protected function getDeleteCheckboxHtml()
    {
        $html = '';
        $html .= '<span class="delete-image">'
            . '<input type="checkbox" name="is_delete_avatar" class="checkbox" id="is_delete_avatar">'
            . '<label for="is_delete_avatar"> Delete Image</label>'
            . '</span>';
        return $html;
    }
}
