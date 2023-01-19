<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 *
 */

namespace Tigren\CustomAddress\Controller\Adminhtml\Region;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Tigren\CustomAddress\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Tigren\CustomAddress\Model\RegionFactory;
use Tigren\CustomAddress\Model\ResourceModel\Region;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Save
 * @package Tigren\CustomAddress\Controller\Adminhtml\Region
 */
class Save extends Action
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var RegionCollectionFactory
     */
    protected $regionCollectionFactory;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var Region
     */
    protected $resourceRegion;



    /**
     * @param Context $context
     * @param RegionFactory $regionFactory
     * @param RegionCollectionFactory $regionCollectionFactory
     * @param Region $resourceRegion
     * @param ResourceConnection $resource
     */
    public function __construct(
        Action\Context          $context,
        RegionFactory           $regionFactory,
        RegionCollectionFactory $regionCollectionFactory,
        Region                  $resourceRegion,
        ResourceConnection      $resource
    )
    {
        parent::__construct($context);
        $this->_resource = $resource;
        $this->regionFactory = $regionFactory;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->resourceRegion = $resourceRegion;

    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            $id = $data['region_id'] ?? false;
            $connection = $this->_resource->getConnection();
            $regionNameTable = $connection->getTableName('directory_country_region_name');

            if ($id) {
                $region = $this->regionCollectionFactory->create()
                    ->addFieldToFilter('region_id', ['eq' => $id])
                    ->setPageSize(1)
                    ->getFirstItem();
                if ($region->getId()) {
                    $region->setData($data);
                }
            } else {
                $region = $this->regionFactory->create();
                $region->addData($data);
            }
            $this->resourceRegion->save($region);

            $locale = $this->_localeResolver->getLocale();
            $sql = $connection->select()->from($regionNameTable)
                ->where('region_id = ?', $region->getId())
                ->where('locale = ?', $locale)
                ->limit(1);
            $record = $connection->fetchAll($sql);
            if (count($record)) {
                $connection->update($regionNameTable, ['name' => $data['code']], "region_id = '{$region->getId()}'  AND locale = '{$locale}'"  );
            } else {
                $connection->insert($regionNameTable, ['name' => $data['code'], 'locale' => $locale, 'region_id' => $region->getId()]);
            }
            $this->messageManager->addSuccessMessage(__('You had saved the region successfully.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('custom_address/region/index');
    }
}
