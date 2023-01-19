<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml\Event\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Tigren\Events\Helper\Data;

/**
 * Class Contact
 *
 * @package Tigren\Events\Block\Adminhtml\Event\Edit\Tab
 */
class Contact extends Generic
{
    /**
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var GroupRepositoryInterface
     */
    protected $_groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var DataObject
     */
    protected $_objectConverter;

    /**
     * @var Data
     */
    protected $_eventsHelper;

    /**
     * Contact constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataObject $objectConverter
     * @param Store $systemStore
     * @param Data $eventsHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataObject $objectConverter,
        Store $systemStore,
        Data $eventsHelper,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_groupRepository = $groupRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_objectConverter = $objectConverter;
        $this->_eventsHelper = $eventsHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return Generic
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        $event = $this->_coreRegistry->registry('events_event');

        /**
         *
         *
         * @var Form $form
         */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('event_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Contact'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'is_show_contact',
            'select',
            [
                'name' => 'is_show_contact',
                'label' => __('Show Contact Information'),
                'title' => __('Show Contact Information'),
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')],
                'onchange' => 'toggleContact(this.value)',
                'after_element_html' => $this->_isShowContactJs(),
            ]
        );
        $fieldset->addField(
            'contact_person',
            'text',
            [
                'name' => 'contact_person',
                'label' => __('Contact Person'),
                'title' => __('Contact Person')
            ]
        );
        $fieldset->addField(
            'contact_phone',
            'text',
            [
                'name' => 'contact_phone',
                'label' => __('Mobile Phone'),
                'title' => __('Mobile Phone')
            ]
        );
        $fieldset->addField(
            'contact_email',
            'text',
            [
                'name' => 'contact_email',
                'label' => __('Email'),
                'title' => __('Email'),
                'class' => __('validate-email'),
            ]
        );
        $fieldset->addField(
            'contact_address',
            'text',
            [
                'name' => 'contact_address',
                'label' => __('Address'),
                'title' => __('Address'),
                'after_element_html' => $event->getId() ? $this->_isShowContactAfterLoadJs() : '',
            ]
        );

        if (!$event->getId()) {
            $event->setData('is_show_contact', '1');
            $this->_isShowContactAfterLoadJs();
        }
        $form->setValues($event->getData());

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @return string
     */
    protected function _isShowContactJs()
    {
        return <<<HTML
    <script>
        function toggleContact(isShow) {
            if (isShow == '1') {
                document.getElementsByClassName('field-contact_person')[0].hidden = false;
                document.getElementsByClassName('field-contact_phone')[0].hidden = false;
                document.getElementsByClassName('field-contact_email')[0].hidden = false;
                document.getElementsByClassName('field-contact_address')[0].hidden = false;
            }
            else {
                document.getElementsByClassName('field-contact_person')[0].hidden = true;
                document.getElementsByClassName('field-contact_phone')[0].hidden = true;
                document.getElementsByClassName('field-contact_email')[0].hidden = true;
                document.getElementsByClassName('field-contact_address')[0].hidden = true;
            }
        }
    </script>
HTML;
    }

    /**
     * @return string
     */
    protected function _isShowContactAfterLoadJs()
    {
        $event = $this->_coreRegistry->registry('events_event');
        $isShow = $event->getData('is_show_contact');
        return <<<HTML
    <script>
        toggleContact($isShow;)
    </script>
HTML;
    }
}
