<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\AdvancedCheckout\Observer;

use Exception;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Api\OrderCustomerManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\CustomerGroupCatalog\Helper\Data;
use Tigren\CustomerGroupCatalog\Model\HistoryFactory;
use Zend_Log_Exception;

/**
 * Class ConvertGuest
 * @package Tigren\AdvancedCheckout\Observer
 */
class ConvertGuest implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var HistoryFactory
     */
    protected $_historyFactory;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var OrderCustomerManagementInterface
     */
    protected $orderCustomerService;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CustomerFactory
     */
    protected $_customer;
    protected $_transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    public $inlineTranslation;
    protected $_urlInterface;

    /**
     * @param OrderCustomerManagementInterface $orderCustomerService
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerFactory $customer
     * @param CollectionFactory $collectionFactory
     * @param HistoryFactory $historyFactory
     * @param Data $helper
     * @param Session $session
     */
    public function __construct(
        OrderCustomerManagementInterface $orderCustomerService,
        OrderFactory                     $orderFactory,
        OrderRepositoryInterface         $orderRepository,
        CustomerFactory                  $customer,
        CollectionFactory                $collectionFactory,
        HistoryFactory                   $historyFactory,
        Data                             $helper,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $inlineTranslation,
        \Magento\Framework\UrlInterface $urlInterface,
        Session                          $session
    ) {
        $this->_session = $session;
        $this->_helper = $helper;
        $this->_historyFactory = $historyFactory;
        $this->_collectionFactory = $collectionFactory;
        $this->orderCustomerService = $orderCustomerService;
        $this->_orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->_customer = $customer;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_urlInterface = $urlInterface;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Log_Exception
     */
    public function execute(Observer $observer)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $orderIds = $observer->getEvent()->getOrderIds();
        $orderId = $orderIds[0];
        $lastOrder = $this->_orderFactory->create()->load($orderId);
        $historyCollection = $this->_historyFactory->create();
        $ruleId = $this->_helper->getRuleId();
        $isLogIn = $this->_session->isLoggedIn();
        $randomPassword = $this->randomPassword();

        if ($isLogIn && $lastOrder->getId()) {
            $customerId = $this->_session->getCustomerId();
            $lastOrder->setCustomerId($customerId);
            $lastOrder->setCustomerIsGuest(0);
            $this->orderRepository->save($lastOrder);

            $arr = [
                'order_id' => $lastOrder->getId(),
                'customer_id' => $customerId,
                'rule_id' => $ruleId
            ];
        } elseif (!$isLogIn && $lastOrder->getId()) {
            $registration = $this->orderCustomerService->create($orderId);
            $new_customer = $this->_customer->create()->load($registration->getId());
            $new_customer->setPassword($randomPassword);
            $new_customer->save();
            $arr = [
                'order_id' => $lastOrder->getId(),
                'customer_id' => $registration->getId(),
                'rule_id' => $ruleId
            ];

            try {
                $this->inlineTranslation->suspend();
                $transport = $this->_transportBuilder
                ->setTemplateIdentifier('auto_create_account_after_order_success')
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars(
                    [
                        'customerName' => $lastOrder->getCustomerFirstname() . ' ' . $lastOrder->getCustomerLastname(),
                        'customerEmail' => $lastOrder->getCustomerEmail(),
                        'autoGenPassword' => $randomPassword,
                        'loginLink' => $this->_urlInterface->getBaseUrl() . '/customer/account/login/'
                    ]
                )
                ->setFrom(['email' => 'thanhdt150@gmail.com', 'name' => 'Mort Store'])
                ->addTo($lastOrder->getCustomerEmail())
                ->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } catch (Exception $e) {
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info(print_r($e, true));
            }
        }

        $historyCollection->addData($arr);
        $historyCollection->save();
    }

    protected function randomPassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = []; //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 6; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
}
