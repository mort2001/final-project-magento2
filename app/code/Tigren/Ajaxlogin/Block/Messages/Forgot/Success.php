<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Block\Messages\Forgot;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

/**
 * Class Success
 *
 * @package Tigren\Ajaxlogin\Block\Messages\Forgot
 */
class Success extends Template
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Success constructor.
     *
     * @param Template\Context $context
     * @param Registry         $registry
     * @param array            $data
     */
    public function __construct(Template\Context $context, Registry $registry, array $data)
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
    }

    /**
     * @return mixed
     */
    public function getEmailFromLayout()
    {
        return $this->_coreRegistry->registry('email');
    }
}
