<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 *
 */

namespace Tigren\CustomAddress\Controller\Adminhtml\Zipcode;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory as SubdistrictCollectionFactory;
use Tigren\CustomAddress\Model\SubdistrictFactory;
use Tigren\CustomAddress\Model\ResourceModel\Subdistrict;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Save
 * @package Tigren\CustomAddress\Controller\Adminhtml\Zipcode
 */
class Save extends Action
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var SubdistrictCollectionFactory
     */
    protected $subdistrictCollectionFactory;

    /**
     * @var SubdistrictFactory
     */
    protected $subdistrictFactory;

    /**
     * @var Subdistrict
     */
    protected $resourceSubdistrict;

    /**
     * @param Context $context
     * @param SubdistrictFactory $subdistrictFactory
     * @param SubdistrictCollectionFactory $subdistrictCollectionFactory
     * @param Subdistrict $resourceSubdistrict
     * @param ResourceConnection $resource
     */
    public function __construct(
        Action\Context          $context,
        SubdistrictFactory           $subdistrictFactory,
        SubdistrictCollectionFactory $subdistrictCollectionFactory,
        Subdistrict                  $resourceSubdistrict,
        ResourceConnection      $resource
    )
    {
        parent::__construct($context);
        $this->_resource = $resource;
        $this->subdistrictFactory = $subdistrictFactory;
        $this->subdistrictCollectionFactory = $subdistrictCollectionFactory;
        $this->resourceSubdistrict = $resourceSubdistrict;

    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            $id = $data['subdistrict_id'] ?? false;
            if ($id) {
                $subdistrict = $this->subdistrictCollectionFactory->create()
                    ->addFieldToFilter('subdistrict_id', ['eq' => $id])
                    ->setPageSize(1)
                    ->getFirstItem();
                if ($subdistrict->getId()) {
                    $subdistrict->setData($data);
                }
            } else {
                $subdistrict = $this->subdistrictFactory->create();
                $subdistrict->addData($data);
            }
            $this->resourceSubdistrict->save($subdistrict);

            $this->messageManager->addSuccessMessage(__('You had saved the zipcode successfully.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('custom_address/zipcode/index');
    }
}
