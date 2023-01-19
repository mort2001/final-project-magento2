<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block\Adminhtml\Block\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\Translate\InlineInterface;
use Tigren\BannerManager\Model\Block;

/**
 * Class Tabs
 *
 * @package Tigren\BannerManager\Block\Adminhtml\Block\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @var InlineInterface
     */
    protected $_translateInline;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param Context $context
     * @param EncoderInterface $jsonEncoder
     * @param Session $authSession
     * @param Registry $registry
     * @param InlineInterface $translateInline
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Session $authSession,
        Registry $registry,
        InlineInterface $translateInline,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_translateInline = $translateInline;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * @return mixed
     */
    public function getBlock()
    {
        if (!$this->getData('bannermanager_block') instanceof Block) {
            $this->setData('bannermanager_block', $this->_coreRegistry->registry('bannermanager_block'));
        }
        return $this->getData('bannermanager_block');
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('bannermanager_block_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Block'));
    }

    /**
     * @return \Magento\Backend\Block\Widget\Tabs
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        $this->addTab(
            'bannermanager_block_edit_tab_main',
            [
                'label' => __('Block Information'),
                'content' => $this->getLayout()->createBlock(
                    'Tigren\BannerManager\Block\Adminhtml\Block\Edit\Tab\Main'
                )->toHtml()
            ]
        );

        $this->addTab(
            'bannergrid',
            [
                'label' => __('Select Banner'),
                'url' => $this->getUrl('bannersmanager/*/banner', ['_current' => true]),
                'class' => 'ajax'
            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * Translate html content
     *
     * @param string $html
     * @return string
     */
    protected function _translateHtml($html)
    {
        $this->_translateInline->processResponseBody($html);
        return $html;
    }
}
