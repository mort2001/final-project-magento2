<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Label
 */


namespace Amasty\Label\Block\Adminhtml\System\Config\Form\Field;

/**
 * Class Swatch
 * @package Amasty\Label\Block\Adminhtml\System\Config\Form\Field
 */
class Swatch extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->getModuleManager()->isEnabled('Amasty_Conf')) {
            $element->setValue('Installed');
            $element->setHtmlId('amasty_is_instaled');
        } else {
            $element->setValue('Not installed');
            $element->setHtmlId('amasty_not_instaled');
        }

        return parent::render($element);
    }
}
