<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Block\Login;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

/**
 * Class Twitter
 *
 * @package Tigren\Ajaxlogin\Block\Login
 */
class Twitter extends Template
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Twitter constructor.
     *
     * @param Template\Context $context
     * @param Registry         $registry
     * @param array            $data
     */
    public function __construct(Template\Context $context, Registry $registry, array $data)
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getUrlLgoin()
    {
        return $this->_coreRegistry->registry('url');
    }

    /**
     *
     */
    public function createWindown()
    {
    }
}
