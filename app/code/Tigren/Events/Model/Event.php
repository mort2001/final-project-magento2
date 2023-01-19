<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model;

use Exception;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\Events\Helper\Data;
use Tigren\Events\Model\Catalog\ProductFactory;

/**
 * Class Event
 *
 * @package Tigren\Events\Model
 */
class Event extends AbstractModel
{
    /**
     *
     */
    const STATUS_ENABLED = 1;
    /**
     *
     */
    const STATUS_DISABLED = 0;
    /**
     *
     */
    const XML_PATH_INVITATION_EMAIL = 'events/general_setting/invitation_email';
    /**
     *
     */
    const XML_PATH_REGISTERED_EMAIL = 'events/general_setting/registered_email';

    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'events_event';

    /**
     * @var string
     */
    protected $_cacheTag = 'events_event';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'events_event';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var UrlInterface
     */
    protected $urlModel;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var
     */
    protected $_stockItem;

    /**
     * @var Catalog\ProductFactory
     */
    protected $_eventsProductFactory;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var Data
     */
    protected $_eventsHelper;

    /**
     * @var FormKey
     */
    protected $_formKey;

    /**
     * Event constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param UrlFactory $urlFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param StockStateInterface $stockItem
     * @param Catalog\ProductFactory $eventsProductFactory
     * @param DateTime $date
     * @param Data $eventsHelper
     * @param FormKey $formKey
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        UrlFactory $urlFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        StockStateInterface $stockItem,
        ProductFactory $eventsProductFactory,
        DateTime $date,
        Data $eventsHelper,
        FormKey $formKey,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->urlModel = $urlFactory->create();
        $this->_productFactory = $productFactory;
        $this->stockItem = $stockItem;
        $this->_eventsProductFactory = $eventsProductFactory;
        $this->_date = $date;
        $this->_eventsHelper = $eventsHelper;
        $this->_formKey = $formKey;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Prepare statuses.
     * Available event events_event_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * @return mixed
     */
    public function getStoreIds()
    {
        return $this->getResource()->getStoreIds($this->getId());
    }

    /**
     * @return mixed
     */
    public function getCategoryIds()
    {
        return $this->getResource()->getCategoryIds($this->getId());
    }

    /**
     * @param  $productId
     * @return mixed
     */
    public function getEventAssociatedPrd($productId)
    {
        return $this->getResource()->getEventAssociatedPrd($productId);
    }

    /**
     * @param  $senderName
     * @param  $recipient
     * @param  $message
     * @throws NoSuchEntityException
     */
    public function sendInvitationEmail($senderName, $recipient, $message)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $this->inlineTranslation->suspend();

        try {
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier(
                    $this->_scopeConfig->getValue(
                        self::XML_PATH_INVITATION_EMAIL,
                        ScopeInterface::SCOPE_STORE,
                        $storeId
                    )
                )
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars(
                    [
                        'event' => $this,
                        'message' => $message,
                        'recipient' => $recipient,
                        'senderName' => $senderName
                    ]
                )
                ->setFrom(['email' => $recipient, 'name' => $senderName])
                ->addTo($recipient)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @param  $participantInfo
     * @throws NoSuchEntityException
     */
    public function sendRegisteredEmail($participantInfo)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $this->inlineTranslation->suspend();

        try {
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier(
                    $this->_scopeConfig->getValue(
                        self::XML_PATH_REGISTERED_EMAIL,
                        ScopeInterface::SCOPE_STORE,
                        $storeId
                    )
                )
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars(['event' => $this, 'participant' => $participantInfo])
                ->setFrom(['email' => $participantInfo['email'], 'name' => 'Registration'])
                ->addTo($participantInfo['email'])
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @return string
     */
    public function getEventUrl()
    {
        return $this->urlModel->getUrl('events/index/view', ['event_id' => $this->getId()]);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAvatarUrl()
    {
        $avatarName = $this->getAvatar();
        if ($avatarName != '') {
            $avatarUrl = $this->_eventsHelper->getImageUrl($avatarName, 'tigren/events/event/avatar/');
        } else {
            $defaultImage = $this->getScopeConfig('events/general_setting/default_image');
            if ($defaultImage && $this->_eventsHelper->getImageUrl($defaultImage, 'tigren/events/')) {
                $avatarUrl = $this->_eventsHelper->getImageUrl($defaultImage, 'tigren/events/');
            } else {
                $avatarUrl = '';
            }
        }
        return $avatarUrl;
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
     * @return string
     */
    public function getAddToCartUrl()
    {
        $productId = (int)$this->getProductId();
        return $this->urlModel->getUrl(
            'events/index/addtocart',
            ['product' => $productId, 'formkey' => $this->_formKey->getFormKey()]
        );
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->getResource()->getProductId($this->getId());
    }

    /**
     * @return string
     */
    public function getRegisterUrl()
    {
        return $this->urlModel->getUrl('events/register/index', ['event_id' => $this->getId()]);
    }

    /**
     * @return mixed
     */
    public function getFavoritedCustomerIds()
    {
        return $this->getResource()->getFavoritedCustomerIds($this->getId());
    }

    /**
     * @param $customerId
     */
    public function addFavorite($customerId)
    {
        $this->getResource()->addFavorite($this->getId(), $customerId);
    }

    /**
     * @param $customerId
     */
    public function removeFavorite($customerId)
    {
        $this->getResource()->removeFavorite($this->getId(), $customerId);
    }

    /**
     * @return bool
     */
    public function isAssociatedProduct()
    {
        $isAssociatedProduct = false;
        $productId = (int)$this->getProductId();
        if ($productId && $productId > 0) {
            $product = $this->_productFactory->create()->load($productId);
            if ($product->getId()) {
                $isAssociatedProduct = true;
            }
        }
        return $isAssociatedProduct;
    }

    /**
     * @return bool
     */
    public function isAllowRegisterEvent()
    {
        $endTime = $this->_date->timestamp($this->getEndTime());
        $registrationDeadline = $this->_date->timestamp($this->getRegistrationDeadline());
        $currentTime = $this->_date->gmtTimestamp();
        $isStillNotDeadline = true;
        if ($registrationDeadline) {
            if ($registrationDeadline < $currentTime) {
                $isStillNotDeadline = false;
            }
        }

        $allowRegister = false;
        if ($this->getAllowRegister() == '1' && $endTime > $currentTime && $isStillNotDeadline && $this->getRemainSlotCount() > 0) {
            $allowRegister = true;
        }
        return $allowRegister;
    }

    /**
     * @return int
     */
    public function getRemainSlotCount()
    {
        $remainSlotCount = ((int)$this->getNoOfParticipant() - (int)$this->getRegisteredCount());
        return $remainSlotCount;
    }

    /**
     * @return float|int
     */
    public function getNoOfParticipant()
    {
        if ($this->getProductId()) {
            return $this->getProductQty();
        } else {
            return $this->getNumberOfParticipant();
        }
    }

    /**
     * @return float|int
     */
    public function getProductQty()
    {
        $product = $this->getProduct();
        if ($product && $productId = $product->getId()) {
            return $this->stockItem->getStockQty($productId, $product->getStore()->getWebsiteId());
        } else {
            return 0;
        }
    }

    /**
     * @return int|void
     */
    public function getRegisteredCount()
    {
        if ($this->getPrice() > 0) {
            $productId = (int)$this->getProductId();
            if ($productId && $productId > 0) {
                $product = $this->_eventsProductFactory->create()->load($productId);
                if ($product->getId()) {
                    return $product->getOrderedQty();
                }
            }
            return 0;
        } else {
            $participantIds = $this->getParticipantIds();
            return count($participantIds);
        }
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        $product = $this->getProduct();
        if ($product && $product->getId()) {
            return $product->getPrice();
        } else {
            return 0;
        }
    }

    /**
     * @return mixed
     */
    public function getParticipantIds()
    {
        return $this->getResource()->getParticipantIds($this->getId());
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Tigren\Events\Model\ResourceModel\Event');
    }
}
