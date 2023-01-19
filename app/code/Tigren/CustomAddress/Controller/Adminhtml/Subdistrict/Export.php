<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Controller\Adminhtml\Subdistrict;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Tigren\CustomAddress\Model\ResourceModel\Subdistrict\CollectionFactory;

/**
 * Class Export
 * @package Tigren\CustomAddress\Controller\Adminhtml\Subdistrict
 */
class Export extends Action
{
    /**
     * @var CollectionFactory
     */
    protected $subdistrictCollectionFactory;
    /**
     * @var
     */
    protected $uploaderFactory;

    /**
     * @throws FileSystemException
     */
    public function __construct(
        Context           $context,
        FileFactory       $fileFactory,
        Filesystem        $filesystem,
        CollectionFactory $subdistrictCollectionFactory)
    {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
        $this->subdistrictCollectionFactory = $subdistrictCollectionFactory;
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
        $subdistrictCollection = $this->subdistrictCollectionFactory->create();
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(print_r($subdistrictCollection->getData(), true));

        $locale = $this->_localeResolver->getLocale();

        $columns = ['city_code', 'code', 'default_name', 'name:' . $locale, 'zipcode'];
        $stream->writeCsv($columns);

        foreach ($subdistrictCollection as $item) {

            $itemData = [];
            $itemData[] = $item->getData('city_code');
            $itemData[] = $item->getData('code');
            $itemData[] = $item->getData('default_name');
            $itemData['name:' . $locale] = $item->getData('subdistrictname'); //Read Colelction of subdistrict to relize
            $itemData[] = $item->getData('zipcode');

            $stream->writeCsv($itemData);
        }

        $content = [];
        $content['type'] = 'filename'; // must keep filename
        $content['value'] = $filepath;
        $content['rm'] = '1'; //remove csv from var folder
        $csvfilename = 'subdistrict-and-zipcode-export-' . $name . '.csv';

        return $this->_fileFactory->create($csvfilename, $content, DirectoryList::VAR_DIR);
    }
}
