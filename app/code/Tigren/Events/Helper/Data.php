<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Helper;

use DateTime;
use DateTimeZone;
use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Catalog data helper
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Filesystem
     */
    protected $_fileSystem;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $fileSystem
     * @param Session $customerSession
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Filesystem $fileSystem,
        Session $customerSession,
        TimezoneInterface $localeDate
    ) {
        $this->_storeManager = $storeManager;
        $this->_fileSystem = $fileSystem;
        $this->_customerSession = $customerSession;
        $this->_localeDate = $localeDate;
        parent::__construct($context);
    }

    /**
     * get image url.
     *
     * @param  $imageName
     * @param  $dir
     * @return string
     * @throws NoSuchEntityException
     */
    public function getImageUrl(
        $imageName,
        $dir
    ) {
        $path = $this->_fileSystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath($dir);
        if (file_exists($path . $imageName)) {
            $path = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $path . $dir . $imageName;
        } else {
            return '';
        }
    }

    /**
     * @return bool
     */
    public function isHeaderlinkEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            'events/general_setting/show_url_in_header_link',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int|null
     */
    public function getCustomerId()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_customerSession->getCustomerId();
        }
        return null;
    }

    /**
     * @param  $dateTime
     * @param  bool     $isReserved
     * @return string
     * @throws Exception
     */
    public function convertTime($dateTime, $isReserved = false)
    {
        if ($isReserved) {
            $date = new DateTime($dateTime, new DateTimeZone($this->_localeDate->getDefaultTimezone()));
            $date->setTimezone(new DateTimeZone($this->_localeDate->getConfigTimezone()));
        } else {
            $date = new DateTime($dateTime, new DateTimeZone($this->_localeDate->getConfigTimezone()));
            $date->setTimezone(new DateTimeZone($this->_localeDate->getDefaultTimezone()));
        }

        $dateTime = $date->format('Y-m-d H:i:s');

        return $dateTime;
    }
}
