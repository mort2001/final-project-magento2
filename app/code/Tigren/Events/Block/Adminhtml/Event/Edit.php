<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml\Event;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;

/**
 * Class Edit
 *
 * @package Tigren\Events\Block\Adminhtml\Event
 */
class Edit extends Container
{
    /**
     * @var
     */
    protected $_coreRegistry;

    /**
     * Edit constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
    }

    /**
     * Retrieve text for header element depending on loaded blocklist
     *
     * @return Phrase
     */
    public function getHeaderText()
    {
        $model = $this->_coreRegistry->registry('events_event');
        if ($model->getId()) {
            return __("Edit Events '%1'", $this->escapeHtml($model->getTitle()));
        } else {
            return __('New Event');
        }
    }

    /**
     * Initialize edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'event_id';
        $this->_blockGroup = 'Tigren_Events';
        $this->_controller = 'adminhtml_event';

        parent::_construct();

        if ($this->_isAllowedAction('Tigren_Events::save')) {
            $this->buttonList->update('save', 'label', __('Save Event'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Tigren_Events::event_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete Event'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('events/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}
