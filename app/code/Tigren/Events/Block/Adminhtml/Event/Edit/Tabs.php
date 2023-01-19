<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml\Event\Edit;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\Translate\InlineInterface;
use Tigren\Events\Model\Event;

/**
 * Class Tabs
 *
 * @package Tigren\Events\Block\Adminhtml\Event\Edit
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
     * Tabs constructor.
     *
     * @param Context $context
     * @param EncoderInterface $jsonEncoder
     * @param Session $authSession
     * @param Registry $registry
     * @param InlineInterface $translateInline
     * @param array $data
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
    public function getEvent()
    {
        if (!$this->getData('events_event') instanceof Event) {
            $this->setData('events_event', $this->_coreRegistry->registry('events_event'));
        }
        return $this->getData('events_event');
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('events_event_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Event'));
    }

    /**
     * @return \Magento\Backend\Block\Widget\Tabs
     * @throws LocalizedException
     * @throws Exception
     */
    protected function _prepareLayout()
    {
        $this->addTab(
            'main',
            [
                'label' => __('Event Information'),
                'content' => $this->getLayout()->createBlock(
                    'Tigren\Events\Block\Adminhtml\Event\Edit\Tab\Main'
                )->toHtml()
            ]
        );

        $this->addTab(
            'organizer',
            [
                'label' => __('Event Organizer Information'),
                'content' => $this->getLayout()->createBlock(
                    'Tigren\Events\Block\Adminhtml\Event\Edit\Tab\Contact'
                )->toHtml()
            ]
        );
        $this->addTab(
            'category',
            [
                'label' => __('Category'),
                'url' => $this->getUrl('events/*/categorygrid', ['_current' => true]),
                'class' => 'ajax'
            ]
        );
        $this->addTab(
            'product',
            [
                'label' => __('Associated Product'),
                'url' => $this->getUrl('events/*/productgrid', ['_current' => true]),
                'class' => 'ajax'
            ]
        );
        $this->addTab(
            'register_user',
            [
                'label' => __('Registered Users'),
                'url' => $this->getUrl('events/*/participantgrid', ['_current' => true]),
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
