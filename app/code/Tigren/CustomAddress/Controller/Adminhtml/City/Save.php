<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 *
 */

namespace Tigren\CustomAddress\Controller\Adminhtml\City;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory as CityCollectionFactory;
use Tigren\CustomAddress\Model\CityFactory;
use Tigren\CustomAddress\Model\ResourceModel\City;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Save
 * @package Tigren\CustomAddress\Controller\Adminhtml\City
 */
class Save extends Action
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var CityCollectionFactory
     */
    protected $cityCollectionFactory;

    /**
     * @var CityFactory
     */
    protected $cityFactory;

    /**
     * @var City
     */
    protected $resourceCity;

    /**
     * @param Context $context
     * @param CityFactory $cityFactory
     * @param CityCollectionFactory $cityCollectionFactory
     * @param City $resourceCity
     * @param ResourceConnection $resource
     */
    public function __construct(
        Action\Context          $context,
        CityFactory           $cityFactory,
        CityCollectionFactory $cityCollectionFactory,
        City                  $resourceCity,
        ResourceConnection      $resource
    )
    {
        parent::__construct($context);
        $this->_resource = $resource;
        $this->cityFactory = $cityFactory;
        $this->cityCollectionFactory = $cityCollectionFactory;
        $this->resourceCity = $resourceCity;

    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            $id = $data['city_id'] ?? false;
            $connection = $this->_resource->getConnection();
            $cityNameTable = $connection->getTableName('directory_region_city_name');

            if ($id) {
                $city = $this->cityCollectionFactory->create()
                    ->addFieldToFilter('city_id', ['eq' => $id])
                    ->setPageSize(1)
                    ->getFirstItem();
                if ($city->getId()) {
                    $city->setData($data);
                }
            } else {
                $city = $this->cityFactory->create();
                $city->addData($data);
            }
            $this->resourceCity->save($city);

            $locale = $this->_localeResolver->getLocale();
            $sql = $connection->select()->from($cityNameTable)
                ->where('city_id = ?', $city->getId())
                ->where('locale = ?', $locale)
                ->limit(1);
            $record = $connection->fetchAll($sql);
            if (count($record)) {
                $connection->update($cityNameTable, ['name' => $data['code']], "city_id = '{$city->getId()}'  AND locale = '{$locale}'"  );
            } else {
                $connection->insert($cityNameTable, ['name' => $data['code'], 'locale' => $locale, 'city_id' => $city->getId()]);
            }
            $this->messageManager->addSuccessMessage(__('You had saved the city successfully.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('custom_address/city/index');
    }
}
