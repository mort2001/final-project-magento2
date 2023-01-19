<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Controller\Adminhtml\City;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory;

/**
 * Class Export
 * @package Tigren\CustomAddress\Controller\Adminhtml\City
 */
class Export extends Action
{
    /**
     * @var CollectionFactory
     */
    protected $cityCollectionFactory;
    /**
     * @var
     */
    protected $uploaderFactory;
    /**
     * @throws FileSystemException
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        CollectionFactory $cityCollectionFactory
    )
    {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
        $this->cityCollectionFactory = $cityCollectionFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR); // VAR Directory Path
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        $name = date('d-m-Y-H-i-s-e');
        //        $name = hash('md5', microtime());
        $filepath = 'export/' . $name . '.csv'; // at Directory path Create a Folder Export and FIle
        $this->directory->create('export');

        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        //column name dispay in your CSV
        $cityCollection = $this->cityCollectionFactory->create();

        $locale = $this->_localeResolver->getLocale();

        $columns = ['region_code','code','default_name','name:' . $locale];
        $stream->writeCsv($columns);

        foreach ($cityCollection as $item) {
            $itemData = [];
            $itemData[] = $item->getData('regioncode');
            $itemData[] = $item->getData('code');
            $itemData[] = $item->getData('default_name');
            $itemData['name:' . $locale] = $item->getData('cityname'); //Read Colelction of city to relize

            $stream->writeCsv($itemData);
        }

        $content = [];
        $content['type'] = 'filename'; // must keep filename
        $content['value'] = $filepath;
        $content['rm'] = '1'; //remove csv from var folder

        $csvfilename = 'city-export-' . $name . '.csv';
        return $this->_fileFactory->create($csvfilename, $content, DirectoryList::VAR_DIR);
    }
}
