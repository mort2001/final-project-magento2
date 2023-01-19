<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block\Adminhtml\Banner\Edit\Tab;

use IntlDateFormatter;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Tigren\BannerManager\Helper\Data;
use Tigren\BannerManager\Model\Banner;

/**
 * Class Main
 *
 * @package Tigren\BannerManager\Block\Adminhtml\Banner\Edit\Tab
 */
class Main extends Generic implements TabInterface
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
    protected $_bannermanagerHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataObject $objectConverter
     * @param Store $systemStore
     * @param Data $bannermanagerHelper
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
        Data $bannermanagerHelper,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_groupRepository = $groupRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_objectConverter = $objectConverter;
        $this->_bannermanagerHelper = $bannermanagerHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab
     *
     * @return             Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Banner Information');
    }

    /**
     * Prepare title for tab
     *
     * @return             Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Banner Information');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return             bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return             bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return                                        Form
     * @throws                                        LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /**
         *
         *
         * @var Banner $model
         */
        $model = $this->_coreRegistry->registry('bannermanager_banner');
        /**
         *
         *
         * @var \Magento\Framework\Data\Form $form
         */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('banner_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('banner_id', 'hidden', ['name' => 'banner_id']);
        }

        $fieldset->addField(
            'banner_title',
            'text',
            ['name' => 'banner_title', 'label' => __('Banner Title'), 'title' => __('Banner Title'), 'required' => true]
        );

        $fieldset->addField(
            'description',
            'textarea',
            ['name' => 'description', 'label' => __('Description'), 'title' => __('Description'), 'required' => false]
        );

        $fieldset->addField(
            'banner_image',
            'file',
            [
                'name' => 'banner_image',
                'label' => __('Desktop Image'),
                'title' => __('Desktop Image'),
                'required' => $model->getId() ? false : true,
                'after_element_html' => $this->getImageHtml('banner_image', $model->getBannerImage())
            ]
        );

        $fieldset->addField(
            'mobile_image',
            'file',
            [
                'name' => 'mobile_image',
                'label' => __('Mobile Image'),
                'title' => __('Mobile Image'),
                'required' => $model->getId() ? false : true,
                'after_element_html' => $this->getImageHtml('mobile_image', $model->getMobileImage())
            ]
        );

        $fieldset->addField(
            'banner_url',
            'text',
            ['name' => 'banner_url', 'label' => __('Banner Url'), 'title' => __('Banner Url'), 'required' => false]
        );

        $fieldset->addField(
            'target',
            'select',
            [
                'label' => __('Target'),
                'title' => __('Target'),
                'name' => 'target',
                'required' => true,
                'values' => $this->_bannermanagerHelper->getTargetOptions()
            ]
        );

        $dateFormat = $this->_localeDate->getDateFormat(IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(IntlDateFormatter::SHORT);

        $style = 'color: #000;background-color: #fff; font-weight: bold; font-size: 13px;';

        $fieldset->addField(
            'start_time',
            'date',
            [
                'name' => 'start_time',
                'label' => __('From'),
                'title' => __('From'),
                'style' => $style,
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'note' => $this->_localeDate->getDateTimeFormat(IntlDateFormatter::SHORT),
            ]
        );

        $fieldset->addField(
            'end_time',
            'date',
            [
                'name' => 'end_time',
                'label' => __('To'),
                'title' => __('To'),
                'style' => $style,
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'note' => $this->_localeDate->getDateTimeFormat(IntlDateFormatter::SHORT)
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Is Active'),
                'title' => __('Is Active'),
                'name' => 'is_active',
                'required' => true,
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        $fieldset->addField(
            'sort_order',
            'text',
            [
                'name' => 'sort_order',
                'label' => __('Sort Order'),
                'title' => __('Sort Order'),
                'required' => false
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param  $field
     * @param  $image
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getImageHtml($field, $image)
    {
        $html = '';
        if ($image) {
            $html .= '<p style="margin-top: 5px">';
            $html .= '<image style="min-width:300px;max-width:100%;" src="' . $this->_bannermanagerHelper->getImageUrl($image) . '" />';
            $html .= '<input type="hidden" value="' . $image . '" name="old_' . $field . '"/>';
            $html .= '</p>';
        }
        return $html;
    }
}
