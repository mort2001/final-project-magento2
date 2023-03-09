<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block;

use Exception;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Tigren\Events\Helper\Data;
use Tigren\Events\Model\Event;

/**
 * Class View
 *
 * @package Tigren\Events\Block
 */
class View extends Template
{
    /**
     * @var mixed
     */
    protected $_event;

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var Data
     */
    protected $_eventsHelper;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var FilterProvider
     */
    private $contentProcessor;

    /**
     * View constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Data $eventsHelper
     * @param ObjectManagerInterface $objectManager
     * @param DateTime $date
     * @param Data $helper
     * @param FilterProvider $contentProcessor
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $eventsHelper,
        ObjectManagerInterface $objectManager,
        DateTime $date,
        Data $helper,
        FilterProvider $contentProcessor,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_eventsHelper = $eventsHelper;
        $this->_objectManager = $objectManager;
        $this->_date = $date;
        $this->_helper = $helper;
        $this->contentProcessor = $contentProcessor;
        $this->_event = $this->getEvent();
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        $event = $this->_coreRegistry->registry('events_event');
        $event->setProduct($event->getProduct());
        return $event;
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [Event::CACHE_TAG . '_' . 'view'];
    }

    /**
     * @return Template
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function _prepareLayout()
    {
        $event = $this->getEvent();
        $this->_addBreadcrumbs($event);

        return parent::_prepareLayout();
    }

    /**
     * @param Event $event
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _addBreadcrumbs(Event $event)
    {
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            );
            $breadcrumbsBlock->addCrumb(
                'events',
                [
                    'label' => __('Events'),
                    'title' => __('Go to Events Page'),
                    'link' => $this->getUrl('events')
                ]
            );
            $breadcrumbsBlock->addCrumb(
                'event',
                [
                    'label' => $event->getTitle(),
                    'title' => $event->getTitle()
                ]
            );
        }
    }

    /**
     * @param  $path
     * @return mixed
     */
    public function getScopeConfig($path)
    {
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getPriceWithCurrency()
    {
        return $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data')->currency(
            number_format(
                $this->_event->getProduct()->getPrice(),
                2
            ),
            true,
            false
        );
    }

    /**
     * @return string
     */
    public function getFavoriteImageSrc()
    {
        if ($this->isFavorited()) {
            return $this->getViewFileUrl('Tigren_Events::images/heart-red.png');
        } else {
            return $this->getViewFileUrl('Tigren_Events::images/heart-white.png');
        }
    }

    /**
     * @return bool
     */
    public function isFavorited()
    {
        $customerId = $this->getCustomerId();
        if (empty($customerId)) {
            return false;
        }

        $favCustomerIds = $this->_event->getFavoritedCustomerIds();
        if (count($favCustomerIds) > 0 && in_array($customerId, $favCustomerIds)) {
            return true;
        }
        return false;
    }

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->_eventsHelper->getCustomerId();
    }

    /**
     * @return string
     */
    public function getAvatarUrl()
    {
        $avatarUrl = $this->_event->getAvatarUrl();
        if ($avatarUrl == '') {
            $avatarUrl = $this->getViewFileUrl('Tigren_Events::images/default_event.jpg');
        }
        return $avatarUrl;
    }

    /**
     * @param  $time
     * @return mixed
     */
    public function getFormattedTime($time)
    {
        $timestamp = $this->_date->timestamp($time);
        return date('M d, Y H:i:s', $timestamp);
    }

    /**
     * @param  $time
     * @return string
     * @throws Exception
     */
    public function getFormattedNoTime($time): string
    {
        $timestamp = $this->_date->timestamp($time);
        return date('M d, Y', $timestamp);
    }

    /**
     * @return string
     */
    public function getFacebookButton()
    {
        $facebookID = '1082368948492595';
        $like_button = true;

        return '
            <div class="facebook_button social-button">
                <div id="fb-root"></div>
                <script>
                    (function(d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) return;
                        js = d.createElement(s); js.id = id;
                        js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.5&appId=' . $facebookID . '";
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, \'script\', \'facebook-jssdk\'));
                </script>
                <div class="fb-like" data-layout="button_count" data-width="400" data-show-faces="false"  data-href="' . $this->_event->getEventUrl() . '"  data-send="' . $like_button . '"></div>
            </div>';
    }

    /**
     * @return string
     */
    public function getTwitterButton()
    {
        return "
            <div class='twitter_button social-button'>
                <a href='https://twitter.com/share' class='twitter-share-button' data-url='" . $this->_event->getEventUrl() . "' >Tweet</a>
                <script>
                    !function(d,s,id){
                        var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';
                        if(!d.getElementById(id)){
                            js=d.createElement(s);
                            js.id=id;
                            js.src=p+'://platform.twitter.com/widgets.js';
                            fjs.parentNode.insertBefore(js,fjs);
                        }
                    }(document, 'script', 'twitter-wjs');
                </script>
            </div>";
    }

    /**
     * @return string
     */
    public function getGooglePlusButton()
    {
        return '
            <div class="google_button social-button">
                <div class="g-plusone" data-size="medium"  data-annotation="bubble"></div>
            </div>
            <script src="https://apis.google.com/js/platform.js" async defer></script>';
    }

    /**
     * @param $event
     * @return string
     * @throws \Exception
     */
    public function getDescriptionHtml($event)
    {
        if ($event->getDescription() !== null) {
            $description = $this->contentProcessor->getBlockFilter()->filter($event->getDescription());
        } else {
            $description = ' ';
        }

        return $description;
    }
}
