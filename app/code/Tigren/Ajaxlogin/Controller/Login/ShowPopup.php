<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Controller\Login;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Tigren\Ajaxlogin\Helper\Data;

/**
 * Class ShowPopup
 *
 * @package Tigren\Ajaxlogin\Controller\Login
 */
class ShowPopup extends Action
{
    /**
     * @var Data
     */
    protected $_ajaxLoginHelper;

    /**
     * ShowPopup constructor.
     *
     * @param Context                       $context
     * @param \Tigren\Ajaxsuite\Helper\Data $ajaxsuiteHelper
     * @param Data                          $ajaxLoginHelper
     */
    public function __construct(
        Context $context,
        \Tigren\Ajaxsuite\Helper\Data $ajaxsuiteHelper,
        Data $ajaxLoginHelper
    ) {
        parent::__construct($context);
        $this->_ajaxLoginHelper = $ajaxLoginHelper;
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $result = [];
        $params = $this->_request->getParams();

        if (!empty($params['isLogin'])) {
            try {
                $htmlPopup = $this->_ajaxLoginHelper->getLoginPopupHtml();
                $result['success'] = true;
                $result['html_popup'] = $htmlPopup;
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('You can\'t login right now.'));
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $result['success'] = false;
            }
        }

        if (!empty($params['isRegister'])) {
            try {
                $htmlPopup = $this->_ajaxLoginHelper->getRegisterPopupHtml();
                $result['success'] = true;
                $result['html_popup'] = $htmlPopup;
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('You can\'t login right now.'));
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $result['success'] = false;
            }
        }

        if (!empty($params['isForgotPassword'])) {
            try {
                $htmlPopup = $this->_ajaxLoginHelper->getForgotPasswordPopupHtml();
                $result['success'] = true;
                $result['html_popup'] = $htmlPopup;
            } catch (Exception $e) {
                $this->messageManager->addException($e, __('You can\'t login right now.'));
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $result['success'] = false;
            }
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
}
